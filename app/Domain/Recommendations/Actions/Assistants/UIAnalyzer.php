<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Recommendations\Recommendation;
use DDD\App\Services\Screenshot\ScreenshotInterface;
use DDD\App\Services\OpenAI\AssistantService;

class UIAnalyzer
{
    use AsAction;

    public $jobTimeout = 180;
    public $jobTries = 2;
    public $jobBackoff = 5;

    protected ScreenshotInterface $screenshotter;
    protected AssistantService $assistant;

    public function __construct(
        ScreenshotInterface $screenshotter,
        AssistantService $assistant
    ){
        $this->screenshotter = $screenshotter;
        $this->assistant = $assistant;
    }

    function handle(
        Recommendation $recommendation, 
    ){
        $recommendation->update(['status' => 'ui_analyzer_in_progress']);

        // $screenshot = $screenshotter->getScreenshot(
        //     url: 'https://centricity.org/loans/vehicle/auto-loans/'
        // );

        $this->assistant->addMessageToThread(
            threadId: $recommendation->thread_id,
            message: 'I\'ve attached a screenshot of my current auto loan page (first file). I\'ve also attached screenshots of two higher performing auto loan pages (second and third files)',
            fileIds: [
                'file-IH4PUBjqstiW72QGLfXnI1DS',
                'file-VUYG4GncvLroPDC9KNhV6lBc',
                'file-AaFdaPUSl65btACwRe0V3vhR',
            ]
        );

        $run = $this->assistant->createRun(
            threadId: $recommendation->thread_id,
            assistantId: 'asst_3tbe9jGHIJcWnmb19GwSMQuM',
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
