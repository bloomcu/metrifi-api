<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Recommendations\Recommendation;
use DDD\App\Services\OpenAI\AssistantService;

class ComponentPicker
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
        $recommendation->update(['status' => 'component_picker_in_progress']);

        $this->assistant->addMessageToThread(
            threadId: $recommendation->thread_id,
            message: 'n/a',
        );

        $run = $this->assistant->createRun(
            threadId: $recommendation->thread_id,
            assistantId: 'asst_pSC8qeDAM1Le5PJVPlfZ9HYA',
        );
        
        $status = $this->assistant->pollRunUntilComplete(
            threadId: $recommendation->thread_id,
            runId: $run['id']
        );

        if ($status === 'completed') {
            // sleep(2);
            return;
        }
    }
}
