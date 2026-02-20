<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Recommendations\Actions\Assistants\WriteContentOutline;
use DDD\App\Neuron\Agents\Recommendations\AnonymizerAgent;
use NeuronAI\Chat\Messages\UserMessage;

class Anonymizer implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;

    public $name = 'anonymizer';
    public $timeout = 60;
    public $tries = 50;
    public $backoff = 5;

    function handle(Recommendation $recommendation)
    {
        $recommendation->update(['status' => $this->name . '_in_progress']);

        $report = $recommendation->metadata['comprehensiveAnalysis'] ?? '';
        if (empty($report)) {
            $recommendation->update(['status' => $this->name . '_completed', 'content' => '']);
            WriteContentOutline::dispatch($recommendation)->delay(now()->addSeconds(8));
            return;
        }

        $message = new UserMessage("## Comprehensive Analysis\n\n" . $report);
        $response = AnonymizerAgent::make()->chat($message);
        $content = $response->getContent();

        $recommendation->update([
            'status' => $this->name . '_completed',
            'content' => $content,
        ]);

        WriteContentOutline::dispatch($recommendation)->delay(now()->addSeconds(8));

        return;
    }
}
