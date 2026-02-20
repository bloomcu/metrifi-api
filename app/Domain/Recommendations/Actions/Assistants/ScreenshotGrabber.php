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
use DDD\Domain\Recommendations\Actions\Assistants\ComparisonAnalyzer;
use DDD\App\Neuron\ImageAttachmentHelper;
use DDD\App\Services\Screenshot\ScreenshotInterface;

class ScreenshotGrabber implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;

    public $name = 'screenshot_grabber';
    public $jobTimeout = 120;
    public $jobTries = 50;
    public $jobBackoff = 5;

    protected ScreenshotInterface $screenshotter;

    public function __construct(ScreenshotInterface $screenshotter)
    {
        $this->screenshotter = $screenshotter;
    }

    function handle(Recommendation $recommendation)
    {
        $recommendation->update(['status' => $this->name . '_in_progress']);

        if (!$recommendation->metadata || !isset($recommendation->metadata['focus']['url'])) {
            $recommendation->update(['status' => $this->name . '_completed']);
            ComparisonAnalyzer::dispatch($recommendation);
            return;
        }

        try {
            $screenshotUrl = $this->screenshotter->getScreenshot(
                url: $recommendation->metadata['focus']['url'],
            );

            $base64 = ImageAttachmentHelper::downloadToBase64($screenshotUrl);

            if ($base64 === null) {
                Log::warning("Could not capture focus screenshot for recommendation ID {$recommendation->id}, continuing without it.");
            }

            $recommendation->update([
                'metadata' => array_merge($recommendation->metadata, [
                    'focusScreenshot' => $base64,
                    'focusScreenshotMediaType' => $base64 ? ImageAttachmentHelper::detectMediaType($screenshotUrl) : null,
                ]),
            ]);
        } catch (Exception $e) {
            Log::error("Error grabbing focus screenshot for recommendation ID {$recommendation->id}: " . $e->getMessage());

            $recommendation->update([
                'status' => $this->name . '_failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $recommendation->update(['status' => $this->name . '_completed']);

        ComparisonAnalyzer::dispatch($recommendation);

        return;
    }
}
