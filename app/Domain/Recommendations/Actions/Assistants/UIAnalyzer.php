<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Recommendations\Actions\Assistants\ContentWriter;
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
        $recommendation->update(['status' => $this->name . '_in_progress']);

        // Start the run if it hasn't been started yet
        if (!isset($recommendation->runs[$this->name])) {

            // $screenshot = $screenshotter->getScreenshot(
            //     url: 'https://centricity.org/loans/vehicle/auto-loans/'
            // );

            $this->assistant->addMessageToThread(
                threadId: $recommendation->thread_id,
                message: 'I\'ve attached a screenshot of my current auto loan page (first file). I\'ve also attached screenshots of other higher performing auto loan pages (subsequent files)',
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

            $recommendation->runs = array_merge($recommendation->runs, [
                $this->name => $run['id'],
            ]);

            $recommendation->save();
        }

        // Check the status of the run
        $status = $this->assistant->getRunStatus(
            threadId: $recommendation->thread_id,
            runId: $recommendation->runs[$this->name]
        );

        // log the status
        Log::info('UIAnalyzer status: ' . $status);

        if (in_array($status, ['requires_action', 'cancelled', 'failed', 'incomplete', 'expired'])) {
            $recommendation->update(['status' => $this->name . '_' . $status]);
            return;
        }

        if ($status !== 'completed') {
            // Dispatch new job to recheck
            self::dispatch($recommendation)->delay(now()->addSeconds($this->backoff));
            return;
        }

        // Run is completed.
        $recommendation->update(['status' => $this->name . '_completed']);
        ContentWriter::dispatch($recommendation);
        return;
    }
}
