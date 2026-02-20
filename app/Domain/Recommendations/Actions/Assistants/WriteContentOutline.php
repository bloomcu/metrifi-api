<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Recommendations\Actions\Assistants\CreateBlocksFromContentOutline;
use DDD\App\Neuron\Agents\Recommendations\WriteContentOutlineAgent;
use NeuronAI\Chat\Messages\UserMessage;

class WriteContentOutline implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;

    public $name = 'content_writer';
    public $timeout = 60;
    public $tries = 50;
    public $backoff = 5;

    function handle(Recommendation $recommendation)
    {
        $recommendation->update(['status' => $this->name . '_in_progress']);

        $context = $this->buildContext($recommendation);
        if (empty($context)) {
            $recommendation->update(['status' => $this->name . '_completed', 'content_outline' => '']);
            CreateBlocksFromContentOutline::dispatch($recommendation);
            return;
        }

        $message = new UserMessage($context);
        $response = WriteContentOutlineAgent::make()->chat($message);
        $contentOutline = $response->getContent();

        $recommendation->update([
            'status' => $this->name . '_completed',
            'content_outline' => $contentOutline,
        ]);

        CreateBlocksFromContentOutline::dispatch($recommendation);

        return;
    }

    protected function buildContext(Recommendation $recommendation): string
    {
        $parts = [];

        if (!empty($recommendation->content)) {
            $parts[] = "## Anonymized Content\n\n" . $recommendation->content;
        }

        if (!empty($recommendation->metadata['comparisonAnalysis'])) {
            $parts[] = "## Comparison Analysis\n\n" . $recommendation->metadata['comparisonAnalysis'];
        }

        if (!empty($recommendation->metadata['comprehensiveAnalysis'])) {
            $parts[] = "## Comprehensive Analysis\n\n" . $recommendation->metadata['comprehensiveAnalysis'];
        }

        if (empty($parts)) {
            return '';
        }

        return implode("\n\n---\n\n", $parts);
    }
}
