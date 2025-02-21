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
    public $timeout = 60;
    public $tries = 50;
    public $backoff = 5;

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

    function handle(Recommendation $recommendation)
    {
        $recommendation->update(['status' => $this->name . '_in_progress']);

        // Get messages from the thread
        $messages = $this->assistant->getMessagesAsString($recommendation->thread_id);

        // Build the prompt for Magic Patterns
        $prompt = "Build section " . ($recommendation->sections_built + 1) . 
                 " from the Content Outline: " . $recommendation->content_outline;

        try {
            // Get design from Magic Patterns
            $magicResponse = $this->magicPatterns->createDesign(
                prompt: $prompt,
                designSystem: 'html',
                styling: 'tailwind',
                shouldAwaitGenerations: true, // Ensure we get completed generations
                requestSummary: false,
                numberOfGenerations: 1
            );

            // Extract the sourceCode from the first generation
            $generatedCode = '';
            if (isset($magicResponse['snapshots']) && 
                !empty($magicResponse['snapshots']) && 
                isset($magicResponse['snapshots'][0]['generations']) && 
                !empty($magicResponse['snapshots'][0]['generations'])) {
                
                $generatedCode = $magicResponse['snapshots'][0]['generations'][0]['sourceCode'] ?? '';
            } else {
                Log::info('No generations found in Magic Patterns response');
                throw new \Exception('No generations found in Magic Patterns response');
            }

            if (empty($generatedCode)) {
                Log::info('No sourceCode found in Magic Patterns response');
                throw new \Exception('No sourceCode found in Magic Patterns response');
            }

            // Convert React to vanilla HTML/CSS using Grok
            $htmlCss = $this->grok->chat(
                instructions: 'You are an expert web developer. Convert the following React code to vanilla HTML and Tailwind CSS. Use FontAwesome icons and maintain the original styling. Return only the HTML code as a string with inline Tailwind CSS classes, nothing else before or after.',
                message: $generatedCode
            );

            // Update the recommendation with the converted section
            $built = $recommendation->sections_built + 1;
            $recommendation->update([
                'sections_built' => $built,
                'prototype' => $recommendation->prototype . $htmlCss,
            ]);

            // If there are more sections to build, dispatch a new instance of the job with a delay
            if ($built < $recommendation->sections_count) {
                self::dispatch($recommendation)->delay(now()->addSeconds(3));
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