<?php

namespace DDD\Http\Blocks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use DDD\App\Controllers\Controller;
use DDD\App\Neuron\Agents\Blocks\BlockContentWriterAgent;
use DDD\App\Neuron\Agents\Blocks\BlockEditorAgent;
use DDD\App\Neuron\Agents\WordPress\WordPressBlockCategorizerAgent;
use DDD\Domain\Blocks\Block;
use DDD\Domain\Blocks\Resources\BlockResource;
use DDD\Domain\Organizations\Organization;
use NeuronAI\Chat\Messages\UserMessage;

class BlockAIController extends Controller
{
    public function edit(Organization $organization, Block $block, Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $payload = json_encode([
            'message' => $validated['message'],
            'element_to_be_changed_in_the_prototype' => $block->html,
        ]);

        try {
            $response = BlockEditorAgent::make()->chat(new UserMessage($payload));
        } catch (\Throwable $e) {
            Log::error('Block edit agent failed', [
                'block_id' => $block->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to edit block: ' . $e->getMessage(),
            ], 502);
        }

        $html = trim((string) $response->getContent());
        // Defensive strip — the prompt asks for raw HTML but models occasionally still wrap.
        $html = trim(preg_replace('/^```(?:html)?\s*|\s*```$/', '', $html));

        $block->update(['html' => $html]);

        return new BlockResource($block);
    }

    public function predictCategory(Organization $organization, Block $block)
    {
        if (empty($block->html)) {
            return response()->json([
                'message' => 'Block has no HTML to categorize.',
            ], 422);
        }

        try {
            $prediction = WordPressBlockCategorizerAgent::make()->structured(
                new UserMessage($block->html),
            );
        } catch (\Throwable $e) {
            Log::error('WordPress block categorization failed', [
                'block_id' => $block->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to categorize block: ' . $e->getMessage(),
            ], 502);
        }

        $type = trim((string) ($prediction->type ?? ''));
        $layout = trim((string) ($prediction->layout ?? ''));
        $dataBlockId = "{$type}--{$layout}";

        // Reject anything that didn't come back as a known schema id. Protects
        // against the model hallucinating a category that doesn't exist in the
        // theme — and against pathological output (e.g. HTML in the field).
        if ($type === '' || $layout === '' || !in_array($dataBlockId, WordPressBlockCategorizerAgent::validBlockIds(), true)) {
            Log::warning('WordPress block categorization returned unknown id', [
                'block_id' => $block->id,
                'returned_type' => $type,
                'returned_layout' => $layout,
            ]);

            return response()->json([
                'message' => 'Categorizer returned an unknown block id.',
                'returned' => ['type' => $type, 'layout' => $layout],
            ], 502);
        }

        return response()->json([
            'data-block-id' => $dataBlockId,
            'type' => $type,
            'layout' => $layout,
        ]);
    }

    public function writeContent(Organization $organization, Block $block, Request $request)
    {
        $validated = $request->validate([
            'schema' => 'required|array',
        ]);

        $payload = json_encode([
            'message' => 'Write content for this block into the provided schema',
            'html' => $block->html,
            'schema' => $validated['schema'],
        ]);

        try {
            $response = BlockContentWriterAgent::make()->chat(new UserMessage($payload));
        } catch (\Throwable $e) {
            Log::error('Block content writer agent failed', [
                'block_id' => $block->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to write block content: ' . $e->getMessage(),
            ], 502);
        }

        $content = trim((string) $response->getContent());
        $content = trim(preg_replace('/^```(?:json)?\s*|\s*```$/', '', $content));

        $parsed = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['message' => 'Invalid JSON response from content writer'], 502);
        }

        return response()->json(['schema_with_content' => $parsed]);
    }
}
