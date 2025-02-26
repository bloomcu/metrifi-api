<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use DDD\Domain\Recommendations\Recommendation;
use DDD\App\Services\OpenAI\AssistantService;

class PageBuilderOpenAI implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;
    
    public $name = 'page_builder';
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
                message: 'Complete the component for section ' . $recommendation->sections_built + 1,
            );
    
            $run = $this->assistant->createRun(
                threadId: $recommendation->thread_id,
                assistantId: 'asst_Wk0cohBVjSRxWLu2XGLd3361',
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

        if (in_array($run['status'], ['requires_action', 'cancelled', 'failed', 'expired'])) {
            // End the job
            Log::info('Ending the job...');
            $recommendation->update(['status' => $this->name . '_' . $run['status']]);
            return;
        }

        if (in_array($run['status'], ['in_progress', 'queued'])) {
            // Dispatch a new instance of the job with a delay to check int
            self::dispatch($recommendation)->delay(now()->addSeconds($this->backoff));
            return;
        }

        if (in_array($run['status'], ['completed', 'incomplete'])) {
            $built = $recommendation->sections_built + 1;
            $message = $this->assistant->getFinalMessage(threadId: $recommendation->thread_id);
            $html = preg_match('/```html(.*?)```/s', $message, $matches) ? $matches[1] : '';
    
            $recommendation->update([
                'status' => $this->name . '_completed',
                'sections_built' => $built,
                'prototype' => $recommendation->prototype . $html,
            ]);
    
            // If there are more sections to build, dispatch a new instance of the job with a delay
            if ($built < $recommendation->sections_count) {
                $recommendation->runs = array_merge($recommendation->runs, [
                    $this->name => null,
                ]);
    
                $recommendation->save();
    
                self::dispatch($recommendation)->delay(now()->addSeconds(8));
                return;
            }
        }
        
        // Done
        $recommendation->update([
            'status' => 'done',
        ]);

        return;
    }
}
