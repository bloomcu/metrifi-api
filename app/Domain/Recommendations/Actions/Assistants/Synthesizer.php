<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Recommendations\Actions\Assistants\Anonymizer;
use DDD\App\Neuron\Agents\Recommendations\SynthesizerAgent;
use NeuronAI\Chat\Attachments\Document;
use NeuronAI\Chat\Attachments\Image;
use NeuronAI\Chat\Enums\AttachmentContentType;
use NeuronAI\Chat\Messages\UserMessage;

class Synthesizer implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;

    public $name = 'synthesizer';
    public $timeout = 60;
    public $tries = 50;
    public $backoff = 5;

    function handle(Recommendation $recommendation)
    {
        // If no prompt but we have comparison analysis, use it as comprehensive analysis and skip
        $metadata = $recommendation->metadata ?? [];
        if (!$recommendation->prompt && !empty($metadata['comparisonAnalysis'])) {
            $metadata['comprehensiveAnalysis'] = $metadata['comparisonAnalysis'];
            $recommendation->update(['metadata' => $metadata, 'status' => $this->name . '_completed']);
            Anonymizer::dispatch($recommendation)->delay(now()->addSeconds(8));
            return;
        }

        // If no prompt and no context to synthesize, skip
        if (!$recommendation->prompt && empty($metadata['comparisonAnalysis']) && empty($metadata['focusScreenshot'])) {
            $recommendation->update(['status' => $this->name . '_completed']);
            Anonymizer::dispatch($recommendation)->delay(now()->addSeconds(8));
            return;
        }

        $recommendation->update(['status' => $this->name . '_in_progress']);

        $message = $this->buildMessage($recommendation);
        $response = SynthesizerAgent::make()->chat($message);
        $comprehensiveAnalysis = $response->getContent();

        $recommendation->update([
            'status' => $this->name . '_completed',
            'metadata' => array_merge($recommendation->metadata ?? [], [
                'comprehensiveAnalysis' => $comprehensiveAnalysis,
            ]),
        ]);

        Anonymizer::dispatch($recommendation)->delay(now()->addSeconds(8));

        return;
    }

    protected function buildMessage(Recommendation $recommendation): UserMessage
    {
        $parts = [];

        if (!empty($recommendation->metadata['comparisonAnalysis'])) {
            $parts[] = "## Comparison Analysis\n\n" . $recommendation->metadata['comparisonAnalysis'];
        }

        if ($recommendation->prompt) {
            $parts[] = "The following information (and files, if attached) are additional information for your consideration: " . $recommendation->prompt;
        } else {
            $parts[] = "Please review the attached screenshot of my current page and produce a comprehensive analysis.";
        }

        $message = new UserMessage(implode("\n\n", $parts));

        if (!empty($recommendation->metadata['focusScreenshot'])) {
            $message->addAttachment(new Image($recommendation->metadata['focusScreenshot'], AttachmentContentType::URL));
        }

        foreach ($recommendation->files as $file) {
            if ($file->pivot->type !== 'additional-information') {
                continue;
            }
            $message->addAttachment(new Document($file->getStorageUrl()));
        }

        return $message;
    }
}
