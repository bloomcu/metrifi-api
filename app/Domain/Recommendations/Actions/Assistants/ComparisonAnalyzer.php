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
use DDD\App\Services\Screenshot\ScreenshotInterface;
use NeuronAI\Chat\Attachments\Image;
use NeuronAI\Chat\Enums\AttachmentContentType;
use NeuronAI\Chat\Messages\UserMessage;

class ComparisonAnalyzer implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;

    public $name = 'comparison_analyzer';
    public $timeout = 240;
    public $tries = 50;
    public $backoff = 5;

    protected ScreenshotInterface $screenshotter;

    public function __construct(ScreenshotInterface $screenshotter)
    {
        $this->screenshotter = $screenshotter;
    }

    function handle(Recommendation $recommendation)
    {
        // If there are no comparisons, skip to the next step
        if (!$recommendation->metadata || !$recommendation->metadata['comparisons']) {
            $recommendation->update(['status' => $this->name . '_completed']);
            Synthesizer::dispatch($recommendation)->delay(now()->addSeconds(8));
            return;
        }

        $recommendation = $recommendation->fresh();
        $recommendation->update(['status' => $this->name . '_in_progress']);

        try {
            // Get comparison screenshots
            $comparisonScreenshots = [];
            foreach ($recommendation->metadata['comparisons'] as $comparison) {
                $comparisonScreenshots[] = $this->screenshotter->getScreenshot(url: $comparison['url']);
            }

            $recommendation->update([
                'metadata' => array_merge($recommendation->metadata, [
                    'comparisonScreenshots' => $comparisonScreenshots,
                ]),
            ]);
        } catch (Exception $e) {
            Log::error("Error grabbing comparison screenshots for recommendation ID {$recommendation->id}: " . $e->getMessage());
            $recommendation->update(['status' => $this->name . '_failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }

        // Build message with image attachments (focus + comparison screenshots as URLs)
        $message = $this->buildMessage($recommendation);
        $response = ComparisonAnalyzerAgent::make()->chat($message);
        $analysis = $response->getContent();

        $recommendation->update([
            'status' => $this->name . '_completed',
            'metadata' => array_merge($recommendation->metadata, [
                'comparisonAnalysis' => $analysis,
            ]),
        ]);

        Synthesizer::dispatch($recommendation)->delay(now()->addSeconds(8));

        return;
    }

    protected function buildMessage(Recommendation $recommendation): UserMessage
    {
        $imageUrls = [];

        if (!empty($recommendation->metadata['focusScreenshot'])) {
            $imageUrls[] = $recommendation->metadata['focusScreenshot'];
        }
        if (!empty($recommendation->metadata['comparisonScreenshots'])) {
            $imageUrls = array_merge($imageUrls, $recommendation->metadata['comparisonScreenshots']);
        }

        $text = 'I\'ve attached a screenshot of my current page called: ' . $recommendation->title . '.';
        if (count($imageUrls) > 1) {
            $text .= ' I\'ve also attached ' . (count($imageUrls) - 1) . ' screenshots of other higher performing pages.';
        }

        $userMessage = new UserMessage($text);
        foreach ($imageUrls as $url) {
            $userMessage->addAttachment(new Image($url, AttachmentContentType::URL));
        }

        return $userMessage;
    }
}
