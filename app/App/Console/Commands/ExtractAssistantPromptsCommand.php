<?php

namespace DDD\App\Console\Commands;

use Illuminate\Console\Command;
use OpenAI\Laravel\Facades\OpenAI;

class ExtractAssistantPromptsCommand extends Command
{
    protected $signature = 'recommendations:extract-assistant-prompts';

    protected $description = 'Extract system prompts from OpenAI assistant IDs used in recommendation generation';

    protected array $assistantIds = [
        'comparison_analyzer' => 'asst_3tbe9jGHIJcWnmb19GwSMQuM',
        'synthesizer' => 'asst_x5feSpZ18zAMOayaItrTDMz9',
        'anonymizer' => 'asst_57iZEjDpsLZMSUnswF8GGb8y',
        'write_content_outline' => 'asst_CMWB6kdTk4KH9zJ3W6U4x8er',
    ];

    public function handle(): int
    {
        $prompts = [];

        foreach ($this->assistantIds as $name => $assistantId) {
            try {
                $assistant = OpenAI::assistants()->retrieve($assistantId);
                $instructions = $assistant->instructions ?? '';
                $prompts[$name] = $instructions;
                $this->info("Extracted prompt for {$name} (" . strlen($instructions) . " chars)");
            } catch (\Throwable $e) {
                $this->error("Failed to retrieve {$name}: " . $e->getMessage());
                $prompts[$name] = $this->getPlaceholderPrompt($name);
            }
        }

        $path = storage_path('app/assistant_prompts.json');
        file_put_contents($path, json_encode($prompts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info("Prompts saved to {$path}");

        return self::SUCCESS;
    }

    protected function getPlaceholderPrompt(string $name): string
    {
        return match ($name) {
            'comparison_analyzer' => 'You are an expert at analyzing web page screenshots. Compare the focus page screenshot with screenshots of higher-performing comparison pages. Identify design patterns, layout differences, and content strategies that make the comparison pages perform better. Provide actionable recommendations.',
            'synthesizer' => 'You synthesize additional information provided by the user with the analysis of the focus page and comparison pages. Integrate this context to enrich the recommendation.',
            'anonymizer' => 'You anonymize content for recommendations. Remove or generalize any identifying information, brand names, or specific details while preserving the structure and meaning of the content.',
            'write_content_outline' => 'You create structured content outlines for web pages. Based on the analysis and anonymized content, produce a clear outline with sections (Section 1, Section 2, etc.) that can be used to build a page.',
            default => 'Complete your instructions.',
        };
    }
}
