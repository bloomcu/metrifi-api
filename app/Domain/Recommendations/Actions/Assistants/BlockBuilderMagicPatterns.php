<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Blocks\Block;
use DDD\App\Neuron\Agents\Recommendations\BlockCategorizerAgent;
use DDD\App\Services\MagicPatterns\MagicPatternsService;
use NeuronAI\Chat\Messages\UserMessage;

class BlockBuilderMagicPatterns implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;

    public $name = 'page_builder';
    public $jobTimeout = 300;

    protected MagicPatternsService $magicPatterns;

    public function __construct(MagicPatternsService $magicPatterns)
    {
        $this->magicPatterns = $magicPatterns;
    }

    public function handle(Recommendation $recommendation, Block $block)
    {
        $recommendation->update(['status' => $this->name . '_in_progress']);

        try {
            // Build the prompt for Magic Patterns
            $prompt = "Build a block based on this content outline:\n\n" . $block->outline;

            // Get design from Magic Patterns
            $magicResponse = $this->magicPatterns->createDesign(
                prompt: $prompt,
            );

            // catch magic patterns exception
            if ($magicResponse === null) {
                // Create an HTML fallback after all retries have failed
                $html = '<section id="section-' . time() . '" class="error p-4 bg-red-50 text-red-700 rounded-lg">
                    <h3 class="font-bold">Error designing block</h3>
                    <p>Magic Patterns encountered an issue while designing this block.</p>
                </section>';
                
                $block->update([
                    'error' => 'Issue encountered while designing this block',
                    'type' => 'error',
                    'html' => $html,
                    'status' => 'done'
                ]);
                
                $recommendation->update([
                    'status' => 'done',
                ]);

                return;
            }

            // Extract the components from the response
            $components = $magicResponse['components'] ?? [];
            
            // Combine all component code into a single string
            $combinedCode = '';
            foreach ($components as $component) {
                $generatedCode = $component['code'] ?? '';
                if (!empty($generatedCode)) {
                    $combinedCode .= "\n\n// Component: " . ($component['name'] ?? 'unnamed') . "\n" . $generatedCode;
                }
            }
            
            try {
                $result = BlockCategorizerAgent::make()->structured(
                    new UserMessage($combinedCode),
                );

                if (empty($result->html)) {
                    Log::error('Magic Patterns: Empty HTML content from BlockCategorizerAgent');
                    throw new \Exception('Magic Patterns: Empty HTML content from BlockCategorizerAgent.');
                }

                $block->update([
                    'type' => $result->category,
                    'html' => $result->html,
                    'status' => 'done'
                ]);

                // Reset retry count on success
                $metadata = $recommendation->metadata ?? [];
                $metadata['retry_count'] = 0;
                
                $recommendation->update([
                    'status' => 'done',
                    'metadata' => $metadata
                ]);
                
            } catch (\Exception $e) {
                // Create an HTML fallback after all retries have failed
                $html = '<section id="section-' . time() . '" class="error p-4 bg-red-50 text-red-700 rounded-lg">
                    <h3 class="font-bold">Error designing block</h3>
                    <p>Magic Patterns encountered an issue while designing this block.</p>
                </section>';
                
                $block->update([
                    'error' => 'Issue encountered while designing this block',
                    'type' => 'error',
                    'html' => $html,
                    'status' => 'done'
                ]);
                
                $recommendation->update([
                    'status' => 'done',
                ]);

                Log::error('Magic Patterns: GPT conversion failed: ' . $e->getMessage());
                throw new \Exception('Magic Patterns: GPT conversion failed: ' . $e->getMessage());
            }
            
        } catch (\Exception $e) {
            // Create an HTML fallback after all retries have failed
            $html = '<section id="section-' . time() . '" class="error p-4 bg-red-50 text-red-700 rounded-lg">
                <h3 class="font-bold">Error designing block</h3>
                <p>Magic Patterns encountered an issue while designing this block.</p>
            </section>';
            
            $block->update([
                'error' => 'Issue encountered while designing this block',
                'type' => 'error',
                'html' => $html,
                'status' => 'done'
            ]);
            
            $recommendation->update([
                'status' => 'done',
            ]);

            // When magic patterns fails, this is the only exception that is thrown
            Log::error("Magic Patterns: Block generation failed: " . $e->getMessage() . ' Time: ' . rand(1, 1000000000));
            throw new \Exception("Magic Patterns: Block generation failed: " . $e->getMessage() . ' Time: ' . rand(1, 1000000000));

            return;
        }

        return;
    }
}