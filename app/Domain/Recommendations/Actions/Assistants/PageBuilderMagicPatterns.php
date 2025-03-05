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
            );

            Log::info('Magic Patterns response: ' . json_encode($magicResponse));

            // Extract the components from the response
            $components = $magicResponse['components'] ?? [];
            
            if (empty($components)) {
                Log::info('No components found in Magic Patterns response');
                $htmlCssSections = '<section id="section-' . time() . '" class="error">Error: No components found in Magic Patterns response</section>';
            } else {
                // Combine all component code into a single string
                $combinedCode = '';
                foreach ($components as $component) {
                    $generatedCode = $component['code'] ?? '';
                    if (!empty($generatedCode)) {
                        $combinedCode .= "\n\n// Component: " . ($component['name'] ?? 'unnamed') . "\n" . $generatedCode;
                    }
                }

                if (empty($combinedCode)) {
                    Log::info('No valid code found in components');
                    $htmlCssSections = '<section id="section-' . time() . '" class="error">Error: No valid code found in components</section>';
                } else {
                    try {
                        // Convert all components at once to vanilla HTML/CSS using Grok
                        $htmlCss = $this->grok->chat(
                            instructions: 'You are an expert web developer. Convert the following React code (which may contain multiple components) to a single cohesive vanilla HTML section with Tailwind CSS. Combine all components into one logical section, maintaining their relationships (e.g., if one component is imported into another). If there are interactive components, include the necessary vanilla JavaScript. Use placeholder images from placehold.co (e.g. https://placehold.co/600x400) where images exist. Use FontAwesome where icons exist. Return only the component HTML code as a string, nothing else before or after. Wrap the result in a <section> tag with a unique id attribute using timestamp.',
                            message: $combinedCode
                        );

                        // Extract the HTML/CSS section from the Grok response
                        $htmlCssSections = preg_match('/```html(.*?)```/s', $htmlCss, $matches) ? trim($matches[1]) : '';

                        if (empty($htmlCssSections)) {
                            Log::info('Failed to extract HTML from Grok response');
                            $htmlCssSections = '<section id="section-' . time() . '" class="error">Error: Failed to convert components</section>';
                        }

                    } catch (\Exception $e) {
                        Log::error('Grok conversion failed: ' . $e->getMessage());
                        $htmlCssSections = '<section id="section-' . time() . '" class="error">Error: Grok conversion failed - ' . $e->getMessage() . '</section>';
                    }
                }
            }

            // Update the recommendation with the converted section
            $built = $recommendation->sections_built + 1;
            $recommendation->update([
                'sections_built' => $built,
                'prototype' => $recommendation->prototype . $htmlCssSections . "\n",
            ]);

            // If there are more sections to build, dispatch a new instance of the job
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
            
            // Capture error and create an HTML fallback
            $errorHtml = '<section id="section-' . time() . '" class="error">MagicPatterns Error: ' . $e->getMessage() . '</section>';
            $built = $recommendation->sections_built + 1;
            
            $recommendation->update([
                'sections_built' => $built,
                'prototype' => $recommendation->prototype . $errorHtml . "\n",
                'status' => $built < $recommendation->sections_count ? $this->name . '_in_progress' : 'done',
            ]);

            // Continue with the next section if applicable
            if ($built < $recommendation->sections_count) {
                self::dispatch($recommendation);
                return;
            }
        }

        return;
    }
}