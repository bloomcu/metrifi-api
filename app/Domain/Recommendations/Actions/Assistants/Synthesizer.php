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
use DDD\Domain\Recommendations\Actions\Assistants\Anonymizer;
use DDD\App\Services\OpenAI\AssistantService;

class Synthesizer implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;

    public $name = 'synthesizer';
    public $timeout = 60;
    public $tries = 50;
    public $backoff = 5;

    protected AssistantService $assistant;

    public function __construct(AssistantService $assistant)
    {
        $this->assistant = $assistant;
    }

    function handle(Recommendation $recommendation)
    {
        // If there is not additional information prompt/images, skip to the next step
        if (!$recommendation->prompt) {
            $recommendation->update(['status' => $this->name . '_completed']);
            Anonymizer::dispatch($recommendation)->delay(now()->addSeconds(8));
            return;
        }

        $recommendation->update(['status' => $this->name . '_in_progress']);

        // Upload the additional information files
        try {
            $files = [];
            foreach ($recommendation->files as $file) {
                $files[] = $this->assistant->uploadFile(
                    url: $file->getStorageUrl(),
                    name: 'additional_information',
                    extension: $file->extension
                );
            }
        } catch (Exception $e) {
            $recommendation->update(['status' => $this->name . '_failed']);
            Log::info('Synthesizer: Failed to upload additional information files');
            return;
        }

        // Start the run if it hasn't been started yet
        if (!isset($recommendation->runs[$this->name])) {
            $this->assistant->addMessageToThread(
                threadId: $recommendation->thread_id,
                message: 'The following information and files are additional information for your consideration: ' . $recommendation->prompt,
                fileIds: [
                    ...$files,
                ]
            );
    
            $run = $this->assistant->createRun(
                threadId: $recommendation->thread_id,
                assistantId: 'asst_x5feSpZ18zAMOayaItrTDMz9',
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
        // Log::info($this->name . ': ' . $run['status']);

        // Issue, end the job
        if (in_array($run['status'], ['requires_action', 'cancelled', 'failed', 'incomplete', 'expired'])) {
            $recommendation->update(['status' => $this->name . '_' . $run['status']]);
            return;
        }

        if (in_array($run['status'], ['in_progress', 'queued'])) {
            // if (isset($run['usage'])) {
            //     Log::info($this->name . ' prompt tokens used: ' . $run['usage']['prompt_tokens']);
            //     Log::info($this->name . ' completion tokens used: ' . $run['usage']['completion_tokens']);
            //     Log::info('Current time: ' . now());
            // }

            // Dispatch a new instance of the job with a delay
            self::dispatch($recommendation)->delay(now()->addSeconds($this->backoff));
            return;
        }

        if (in_array($run['status'], ['completed', 'incomplete'])) {
            $recommendation->update(['status' => $this->name . '_completed']);
            Anonymizer::dispatch($recommendation)->delay(now()->addSeconds(8));
            return;
        }
        
        return;
    }
}
