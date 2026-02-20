<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Exception;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Recommendations\Actions\Assistants\Synthesizer;
use DDD\App\Neuron\Agents\Recommendations\ComparisonAnalyzerAgent;
use DDD\App\Neuron\ImageAttachmentHelper;
use DDD\App\Services\Screenshot\ScreenshotInterface;
use NeuronAI\Chat\Messages\UserMessage;

class ComparisonAnalyzer implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;

    public $name = 'comparison_analyzer';
    public $jobTimeout = 240;
    public $jobTries = 50;
    public $jobBackoff = 5;

    protected ScreenshotInterface $screenshotter;

    public function __construct(ScreenshotInterface $screenshotter)
    {
        $this->screenshotter = $screenshotter;
    }

    function handle(Recommendation $recommendation)
    {
        if (!$recommendation->metadata || !$recommendation->metadata['comparisons']) {
            $recommendation->update(['status' => $this->name . '_completed']);
            Synthesizer::dispatch($recommendation)->delay(now()->addSeconds(8));
            return;
        }

        $recommendation = $recommendation->fresh();
        $recommendation->update(['status' => $this->name . '_in_progress']);

        try {
            $this->captureComparisonScreenshots($recommendation);

            $message = $this->buildMessage($recommendation);
            $response = ComparisonAnalyzerAgent::make()->chat($message);
            $analysis = $response->getContent();

            $recommendation->update([
                'status' => $this->name . '_completed',
                'metadata' => array_merge($recommendation->metadata, [
                    'comparisonAnalysis' => $analysis,
                ]),
            ]);
        } catch (Exception $e) {
            Log::error("ComparisonAnalyzer failed for recommendation ID {$recommendation->id}: " . $e->getMessage());
            $recommendation->update(['status' => $this->name . '_failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }

        Synthesizer::dispatch($recommendation)->delay(now()->addSeconds(8));

        return;
    }

    protected function captureComparisonScreenshots(Recommendation $recommendation): void
    {
        $screenshots = [];
        $mediaType = 'image/jpeg';

        foreach ($recommendation->metadata['comparisons'] as $comparison) {
            $screenshotUrl = $this->screenshotter->getScreenshot(url: $comparison['url']);
            $mediaType = ImageAttachmentHelper::detectMediaType($screenshotUrl);
            $base64 = ImageAttachmentHelper::downloadToBase64($screenshotUrl);

            if ($base64 === null) {
                Log::warning("Could not capture comparison screenshot for {$comparison['url']}, skipping.");
            }

            $screenshots[] = $base64;
        }

        $recommendation->update([
            'metadata' => array_merge($recommendation->metadata, [
                'comparisonScreenshots' => $screenshots,
                'comparisonScreenshotMediaType' => $mediaType,
            ]),
        ]);
    }

    protected function buildMessage(Recommendation $recommendation): UserMessage
    {
        $metadata = $recommendation->metadata;
        $attachedCount = 0;

        $focusBase64 = $metadata['focusScreenshot'] ?? null;
        $focusMediaType = $metadata['focusScreenshotMediaType'] ?? 'image/jpeg';
        $comparisonScreenshots = array_filter($metadata['comparisonScreenshots'] ?? []);
        $comparisonMediaType = $metadata['comparisonScreenshotMediaType'] ?? 'image/jpeg';

        $userMessage = new UserMessage('');

        if ($focusBase64) {
            $userMessage->addAttachment(ImageAttachmentHelper::fromBase64($focusBase64, $focusMediaType));
            $attachedCount++;
        }

        foreach ($comparisonScreenshots as $base64) {
            $userMessage->addAttachment(ImageAttachmentHelper::fromBase64($base64, $comparisonMediaType));
            $attachedCount++;
        }

        $text = 'My current page is called: ' . $recommendation->title . '.';

        if ($focusBase64) {
            $text = 'I\'ve attached a screenshot of my current page called: ' . $recommendation->title . '.';
        }

        $comparisonCount = count($comparisonScreenshots);
        if ($comparisonCount > 0) {
            $text .= ' I\'ve also attached ' . $comparisonCount . ' screenshot(s) of other higher performing pages.';
        }

        if ($attachedCount === 0) {
            $text .= ' (Screenshots could not be captured, please provide analysis based on the page name and context.)';
        }

        $userMessage->setContent($text);

        return $userMessage;
    }
}
