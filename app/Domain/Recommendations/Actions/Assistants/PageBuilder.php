<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Recommendations\Recommendation;
use DDD\App\Services\OpenAI\AssistantService;

class PageBuilder
{
    use AsAction;
    
    public $jobTimeout = 180;
    public $jobTries = 2;
    public $jobBackoff = 5;

    protected AssistantService $assistant;

    public function __construct(
        AssistantService $assistant
    ){
        $this->assistant = $assistant;
    }

    function handle(
        Recommendation $recommendation, 
    ){
        $recommendation->update(['status' => 'page_builder_in_progress']);

        $this->assistant->addMessageToThread(
            threadId: $recommendation->thread_id,
            message: 'n/a',
        );

        $run = $this->assistant->createRun(
            threadId: $recommendation->thread_id,
            assistantId: 'asst_Wk0cohBVjSRxWLu2XGLd3361',
        );
        
        $status = $this->assistant->pollRunUntilComplete(
            threadId: $recommendation->thread_id,
            runId: $run['id']
        );

        if ($status === 'completed') {
            $message = $this->assistant->getFinalMessage(threadId: $recommendation->thread_id);

            $recommendation->update([
                'status' => 'complete',
                'content' => $message
            ]);

            return;
        }
    }
}
