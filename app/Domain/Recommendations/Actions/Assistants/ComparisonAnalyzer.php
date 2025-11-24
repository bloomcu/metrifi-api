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
use DDD\App\Services\Screenshot\ScreenshotInterface;
use DDD\App\Services\OpenAI\AssistantService;

class ComparisonAnalyzer implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;

    public $name = 'comparison_analyzer';
    public $timeout = 240;
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
        // If there is not comparisons, skip to the next step
        if (!$recommendation->metadata || !$recommendation->metadata['comparisons']) {
            $recommendation->update(['status' => $this->name . '_completed']);
            Synthesizer::dispatch($recommendation)->delay(now()->addSeconds(8));
            return;
        }

        // Refresh the recommendation so we get the file ids
        $recommendation = $recommendation->fresh();

        // Set status to in progress
        $recommendation->update(['status' => $this->name . '_in_progress']);

        try {
            // Iterate over the comparisons and get the screenshots
            $comparisonScreenshots = [];

            foreach ($recommendation->metadata['comparisons'] as $comparison) {
                $comparisonScreenshot = $this->screenshotter->getScreenshot(
                    url: $comparison['url'],
                );

                $comparisonScreenshots[] = $comparisonScreenshot;
            }

            $recommendation->update([
                'metadata' => array_merge($recommendation->metadata, [
                    'comparisonScreenshots' => $comparisonScreenshots,
                ]),
            ]);
        } catch (Exception $e) {
            // Log the error message for debugging purposes
            Log::error("Error grabbing comparison screenshots for recommendation ID {$recommendation->id}: " . $e->getMessage());

            // Gracefully fail the job
            $recommendation->update([
                'status' => $this->name . '_failed',
                'error_message' => $e->getMessage(), // Optionally store the error message in the metadata
            ]);

            // Rethrow the exception to retry based on $tries/backoff
            throw $e;
        }

        // Upload the screenshots
        // Wait before uploading the screenshots
        sleep(5);
        
        $comparisonScreenshotIds = [];
        
        if ($recommendation->metadata['comparisonScreenshots']) {
            foreach ($recommendation->metadata['comparisonScreenshots'] as $index => $comparisonScreenshot) {
                try {
                    $fileId = $this->assistant->uploadFile(
                        url: $comparisonScreenshot,
                        name: 'screenshot',
                        extension: 'png'
                    );
                    $comparisonScreenshotIds[] = $fileId;
                } catch (Exception $e) {
                    // Log the error for this specific screenshot but continue with others
                    Log::warning("Failed to upload comparison screenshot {$index} for recommendation ID {$recommendation->id}: " . $e->getMessage());
                    // Continue processing other screenshots
                }
            }
        }
        
        // Log if all screenshots failed to upload
        if (empty($comparisonScreenshotIds) && !empty($recommendation->metadata['comparisonScreenshots'])) {
            Log::warning("All comparison screenshots failed to upload for recommendation ID {$recommendation->id}. Continuing without screenshots.");
        }

        // Start the run if it hasn't been started yet
        if (!isset($recommendation->runs[$this->name])) {
            // Only add message with screenshots if we have successfully uploaded files
            if (!empty($comparisonScreenshotIds)) {
                $this->assistant->addMessageToThread(
                    threadId: $recommendation->thread_id,
                    message: 'I\'ve attached screenshots of other higher performing pages (' . count($comparisonScreenshotIds) . ' files).',
                    fileIds: [
                        ...$comparisonScreenshotIds,
                    ]
                );
            } else {
                // If no screenshots were uploaded, add a message explaining that
                $this->assistant->addMessageToThread(
                    threadId: $recommendation->thread_id,
                    message: 'I attempted to attach screenshots of other higher performing pages, but they were unavailable. Please proceed with the analysis based on the comparison URLs provided.',
                    fileIds: []
                );
            }
    
            $run = $this->assistant->createRun(
                threadId: $recommendation->thread_id,
                assistantId: 'asst_3tbe9jGHIJcWnmb19GwSMQuM',
            );

            $recommendation->runs = array_merge($recommendation->runs, [
                $this->name => $run['id'],
            ]);

            $recommendation->save();
        }

        // Check the status of the run
        $run = $this->assistant->getRun(
            threadId: $recommendation->thread_id,
            runId: $recommendation->runs[$this->name]
        );

        // Issue, end the job
        if (in_array($run['status'], ['requires_action', 'cancelled', 'failed', 'incomplete', 'expired'])) {
            $recommendation->update(['status' => $this->name . '_' . $run['status']]);
            return;
        }

        if (in_array($run['status'], ['in_progress', 'queued'])) {
            // Dispatch a new instance of the job with a delay
            self::dispatch($recommendation)->delay(now()->addSeconds($this->backoff));
            return;
        }

        if (in_array($run['status'], ['completed', 'incomplete'])) {
            $recommendation->update(['status' => $this->name . '_completed']);
            Synthesizer::dispatch($recommendation)->delay(now()->addSeconds(8));
            return;
        }
        
        return;
    }
}
