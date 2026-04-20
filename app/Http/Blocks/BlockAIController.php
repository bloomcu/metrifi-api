<?php

namespace DDD\Http\Blocks;

use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;
use DDD\App\Controllers\Controller;
use DDD\App\Services\OpenAI\AssistantService;
use DDD\Domain\Blocks\Block;
use DDD\Domain\Blocks\Resources\BlockResource;
use DDD\Domain\Organizations\Organization;

class BlockAIController extends Controller
{
    public function edit(Organization $organization, Block $block, Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a coding expert. I am requesting changes to an HTML element (a section). '
                        . 'Modify the provided element_to_be_changed_in_the_prototype based on the user\'s request. '
                        . 'Return ONLY the updated element as a raw HTML string. '
                        . 'Do NOT wrap the output in Markdown, code blocks (like ```html), JSON, objects, or any other formatting. '
                        . 'Return ONLY the raw HTML string of the modified element.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'message' => $validated['message'],
                        'element_to_be_changed_in_the_prototype' => $block->html,
                    ]),
                ],
            ],
        ]);

        $html = $response->choices[0]->message->content;
        $html = preg_replace('/```html\s*|\s*```/', '', $html);
        $html = trim($html);

        $block->update(['html' => $html]);

        return new BlockResource($block);
    }

    public function predictCategory(Organization $organization, Block $block, AssistantService $assistant)
    {
        $assistantId = 'asst_jjPmiRkOknWPAYxPdyfQLpvJ';

        $run = $assistant->createAndRunThread($assistantId, $block->html);
        $message = $assistant->pollForFinalMessage($run->threadId, $run->id);

        $decoded = json_decode($message, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['data-block-id'])) {
            return response()->json($decoded);
        }

        return response()->json(['data-block-id' => $message]);
    }

    public function writeContent(Organization $organization, Block $block, Request $request)
    {
        $validated = $request->validate([
            'schema' => 'required|array',
        ]);

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert at writing content in a json object. I am requesting content for a block. I will provide the html of a block and the json schema I need the content written in. '
                        . 'IMPORTANT: Remove unused keys in the json–these are keys with empty values. Don\'t fill in gaps in the content. That\'s not your job. Your only job is to delete placeholder content and transfer existing content.'
                        . 'IMPORTANT: Never remove the data_source key.'
                        . 'IMPORTANT: Do not remove image keys.'
                        . 'IMPORTANT: Do not remove keys that are arrays containing ids.'
                        . 'IMPORTANT: The "title key" is almost always used. The "sub_title" key is usually used.'
                        . 'IMPORTANT: Your response MUST be pure JSON without any markdown wrappers, code blocks, or additional text. Do NOT wrap the response in ```json ... ``` or any other markdown. Provide only the JSON object as plain text.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'message' => 'Write content for this block into the provided schema',
                        'html' => $block->html,
                        'schema' => $validated['schema'],
                    ]),
                ],
            ],
        ]);

        $content = trim($response->choices[0]->message->content);
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $content);

        $parsed = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['message' => 'Invalid JSON response from OpenAI'], 502);
        }

        return response()->json(['schema_with_content' => $parsed]);
    }
}
