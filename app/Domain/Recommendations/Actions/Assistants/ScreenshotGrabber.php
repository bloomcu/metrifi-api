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
use DDD\App\Services\OpenAI\AssistantService;

class ScreenshotGrabber implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;

    public $name = 'screenshot_grabber';
    public $timeout = 60;
    public $tries = 50;
    public $backoff = 5;

    protected ScreenshotInterface $screenshotter;
    protected AssistantService $assistant;

    public function __construct(ScreenshotInterface $screenshotter, AssistantService $assistant)
    {
        $this->screenshotter = $screenshotter;
        $this->assistant = $assistant;
    }

    function handle(Recommendation $recommendation)
    {
        $recommendation->update(['status' => $this->name . '_in_progress']);

        try {
            // Get focus screenshot
            $focusScreenshot = $this->screenshotter->getScreenshot(
                url: $recommendation->metadata['focus']['url'],
            );

            $recommendation->update([
                'metadata' => array_merge($recommendation->metadata, [
                    'focusScreenshot' => $focusScreenshot,
                ]),
            ]);
        } catch (Exception $e) {
            // Log the error message for debugging purposes
            Log::error("Error grabbing focus screenshot for recommendation ID {$recommendation->id}: " . $e->getMessage());

            // Gracefully fail the job
            $recommendation->update([
                'status' => $this->name . '_failed',
                'error_message' => $e->getMessage(), // Optionally store the error message in the metadata
            ]);

            // Rethrow the exception to retry based on $tries/backoff
            throw $e;
        }

        // Upload the screenshot
        try {
            if ($recommendation->metadata['focusScreenshot']) {
                $focusScreenshotId = $this->assistant->uploadFile(
                    url: $recommendation->metadata['focusScreenshot'],
                    name: 'screenshot',
                    extension: 'png'
                );
            }
        } catch (Exception $e) {
            // Log the error message for debugging purposes
            Log::error("Error uploading focus screenshots for recommendation ID {$recommendation->id}: " . $e->getMessage());

            // Gracefully fail the job
            $recommendation->update([
                'status' => $this->name . '_failed',
                'error_message' => $e->getMessage(), // Optionally store the error message in the metadata
            ]);

            // Rethrow the exception to retry based on $tries/backoff
            throw $e;
        }

        // Add message to thread, do not run
        if (!isset($recommendation->runs[$this->name])) {
            $this->assistant->addMessageToThread(
                threadId: $recommendation->thread_id,
                message: 'I\'ve attached a screenshot of my current page called' . $recommendation->title . '.',
                fileIds: [
                    $focusScreenshotId,
                ]
            );
        }

        $recommendation->update(['status' => $this->name . '_completed']);

        ComparisonAnalyzer::dispatch($recommendation);

        return;
    }
}
