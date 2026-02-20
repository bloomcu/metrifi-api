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
use DDD\App\Services\Screenshot\ScreenshotInterface;

class ScreenshotGrabber implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;

    public $name = 'screenshot_grabber';
    public $timeout = 120;
    public $tries = 50;
    public $backoff = 5;

    protected ScreenshotInterface $screenshotter;

    public function __construct(ScreenshotInterface $screenshotter)
    {
        $this->screenshotter = $screenshotter;
    }

    function handle(Recommendation $recommendation)
    {
        $recommendation->update(['status' => $this->name . '_in_progress']);

        // Skip screenshot grabbing if no focus URL is available
        if (!$recommendation->metadata || !isset($recommendation->metadata['focus']['url'])) {
            $recommendation->update(['status' => $this->name . '_completed']);
            ComparisonAnalyzer::dispatch($recommendation);
            return;
        }

        try {
            // Get focus screenshot (URL stored in metadata for Neuron image attachments)
            $focusScreenshot = $this->screenshotter->getScreenshot(
                url: $recommendation->metadata['focus']['url'],
            );

            $recommendation->update([
                'metadata' => array_merge($recommendation->metadata, [
                    'focusScreenshot' => $focusScreenshot,
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
