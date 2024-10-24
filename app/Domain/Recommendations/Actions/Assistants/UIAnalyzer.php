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

class UIAnalyzer implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;

    public $name = 'ui_analyzer';
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
        // Refresh the recommendation so we get the file ids
        $recommendation = $recommendation->fresh();

        // Set status to in progress
        $recommendation->update(['status' => $this->name . '_in_progress']);

        // Upload the additional information files
        try {
            $files = [];
            foreach ($recommendation->files as $file) {
                Log::info('PDF', ['pdf' => $file->getStorageUrl()]);

                $files[] = $this->assistant->uploadFile(
                    url: $file->getStorageUrl(),
                    name: 'additional_information',
                    extension: $file->extension
                );
            }
        } catch (Exception $e) {
            $recommendation->update(['status' => $this->name . '_failed']);
            Log::info('UIAnalyzer: Failed to upload additional information files');
            return;
        }

        // Upload the screenshots
        try {
            $focusScreenshotId = $this->assistant->uploadFile(
                url: $recommendation->metadata['focusScreenshot'],
                name: 'screenshot',
                extension: 'png'
            );
    
            $comparisonScreenshotIds = [];
            foreach ($recommendation->metadata['comparisonScreenshots'] as $comparisonScreenshot) {
                $comparisonScreenshotIds[] = $this->assistant->uploadFile(
                    url: $comparisonScreenshot,
                    name: 'screenshot',
                    extension: 'png'
                );
            }
        } catch (Exception $e) {
            $recommendation->update(['status' => $this->name . '_failed']);
            return;
        }

        // Start the run if it hasn't been started yet
        if (!isset($recommendation->runs[$this->name])) {
            $this->assistant->addMessageToThread(
                threadId: $recommendation->thread_id,
                message: 'I\'ve attached a screenshot of my current ' . $recommendation->title . ' page (first file). I\'ve also attached screenshots of other higher performing pages (subsequent ' . count($comparisonScreenshotIds) . ' files). Any remaining files attached are additional information for your consideration (there are ' . count($files) . ' additional files).',
                fileIds: [
                    $focusScreenshotId,
                    ...$comparisonScreenshotIds,
                    ...$files,
                ]
            );
    
            $run = $this->assistant->createRun(
                threadId: $recommendation->thread_id,
                assistantId: 'asst_3tbe9jGHIJcWnmb19GwSMQuM',
                // maxPromptTokens: 2000,
                // maxCompletionTokens: 2000,
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

        // Log the status
        Log::info($this->name . ': ' . $run['status']);

        // Issue, end the job
        if (in_array($run['status'], ['requires_action', 'cancelled', 'failed', 'incomplete', 'expired'])) {
            $recommendation->update(['status' => $this->name . '_' . $run['status']]);
            return;
        }

        if (in_array($run['status'], ['in_progress', 'queued'])) {
            // Log::info($this->name . ' prompt tokens allowed: ' . $run['max_prompt_tokens']);
            // Log::info($this->name . ' completion tokens allowed: ' . $run['max_completion_tokens']);
            if (isset($run['usage'])) {
                Log::info($this->name . ' prompt tokens used: ' . $run['usage']['prompt_tokens']);
                Log::info($this->name . ' completion tokens used: ' . $run['usage']['completion_tokens']);
                Log::info('Current time: ' . now());
            }

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
