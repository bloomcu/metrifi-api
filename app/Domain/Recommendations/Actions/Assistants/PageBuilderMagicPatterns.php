<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use DDD\App\Services\MagicPatterns\MagicPatternsService;
use DDD\App\Services\Grok\GrokService;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use DDD\Domain\Recommendations\Recommendation;
use DDD\App\Services\OpenAI\AssistantService;

class PageBuilderMagicPatterns implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;
    
    public $name = 'page_builder';
    public $timeout = 300;

    protected AssistantService $assistant;
    protected MagicPatternsService $magicPatterns;
    protected GrokService $grok;

    public function __construct(
        AssistantService $assistant,
        MagicPatternsService $magicPatterns,
        GrokService $grok
    ) {
        $this->assistant = $assistant;
        $this->magicPatterns = $magicPatterns;
        $this->grok = $grok;
    }

    public function handle(Recommendation $recommendation)
    {
        $recommendation->update(['status' => $this->name . '_in_progress']);

        // Build the prompt for Magic Patterns
        $prompt = "Build section " . ($recommendation->sections_built + 1) . 
                 " from the Content Outline: " . $recommendation->content_outline;

        try {
            // Get design from Magic Patterns
            $magicResponse = $this->magicPatterns->createDesign(
                prompt: $prompt,
                // presetId: 'html-tailwind',
            );

            // Extract the components from the response
            $components = $magicResponse['components'] ?? [];
            
            if (empty($components)) {
                Log::info('No components found in Magic Patterns response');
                throw new \Exception('No components found in Magic Patterns response');
            }

            // Convert each React component to vanilla HTML/CSS using Grok
            $htmlCssSections = '';
            foreach ($components as $component) {
                $generatedCode = $component['code'] ?? '';
                
                if (empty($generatedCode)) {
                    Log::info('No code found for component: ' . ($component['name'] ?? 'unknown'));
                    continue; // Skip this component if no code is present
                }

                // Convert React to vanilla HTML/CSS using Grok
                $htmlCss = $this->grok->chat(
                    instructions: 'You are an expert web developer. Convert the following React code to vanilla HTML, JavaScript and Tailwind CSS. Use placeholder images from placehold.co (e.g. https://placehold.co/600x400) where images exist. If the React code contains small components such as a button, use that componet inside the main component (e.g., inside the hero, feature, etc). Return only the HTML code as a string with inline Tailwind CSS classes, nothing else before or after.',
                    message: $generatedCode
                );

                // Extract the HTML/CSS section from the Grok response
                $cleanHtmlCss = preg_match('/```html(.*?)```/s', $htmlCss, $matches) ? trim($matches[1]) : '';

                if (empty($cleanHtmlCss)) {
                    Log::info('Failed to extract HTML from Grok response for component: ' . ($component['name'] ?? 'unknown'));
                    continue; // Skip this component if extraction fails
                }

                // Append the converted HTML/CSS to the sections string
                $htmlCssSections .= $cleanHtmlCss . "\n";
            }

            if (empty($htmlCssSections)) {
                Log::info('No valid HTML/CSS generated from components');
                throw new \Exception('No valid HTML/CSS generated from components');
            }

            // Update the recommendation with the converted section
            $built = $recommendation->sections_built + 1;
            $recommendation->update([
                'sections_built' => $built,
                'prototype' => $recommendation->prototype . $htmlCssSections,
            ]);

            // If there are more sections to build, dispatch a new instance of the job with a delay
            if ($built < $recommendation->sections_count) {
                self::dispatch($recommendation);
                return;
            }
            
            // Done, no more sections to build
            $recommendation->update([
                'status' => 'done',
            ]);

        } catch (\Exception $e) {
            Log::error('Page generation failed: ' . $e->getMessage());
            $recommendation->update(['status' => 'failed']);
            throw $e;
        }

        return;
    }
}