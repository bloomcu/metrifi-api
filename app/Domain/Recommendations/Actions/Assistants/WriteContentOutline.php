<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Recommendations\Actions\Assistants\CreateBlocksFromContentOutline;
use DDD\App\Services\OpenAI\AssistantService;

class WriteContentOutline implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;
    
    public $name = 'content_writer';
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
        $recommendation->update(['status' => $this->name . '_in_progress']);

        // Start the run if it hasn't been started yet
        if (!isset($recommendation->runs[$this->name])) {
            $this->assistant->addMessageToThread(
                threadId: $recommendation->thread_id,
                message: 'Complete your instructions.',
            );
    
            $run = $this->assistant->createRun(
                threadId: $recommendation->thread_id,
                assistantId: 'asst_CMWB6kdTk4KH9zJ3W6U4x8er',
                // maxPromptTokens: 10000,
                // maxCompletionTokens: 10000,
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

        if (in_array($run['status'], ['requires_action', 'cancelled', 'failed', 'incomplete', 'expired'])) {
            // End the job
            Log::info('Ending the job...');
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
            $message = $this->assistant->getFinalMessage(threadId: $recommendation->thread_id);

            $recommendation->update([
              'status' => $this->name . '_completed',
              'content_outline' => $message,
            ]);

            // SectionCounter::dispatch($recommendation)->delay(now()->addSeconds(8));
            CreateBlocksFromContentOutline::dispatch($recommendation);
            return;
        }

        return;
    }
}
