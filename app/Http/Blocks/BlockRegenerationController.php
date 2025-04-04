<?php

namespace DDD\Http\Blocks;

use DDD\App\Controllers\Controller;
use DDD\Domain\Blocks\Block;
use DDD\Domain\Blocks\Resources\BlockResource;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Recommendations\Actions\Assistants\BlockBuilderMagicPatterns;
use Illuminate\Http\Response;

class BlockRegenerationController extends Controller
{
    /**
     * Regenerate a block
     */
    public function store(Organization $organization, Block $block)
    {
        // Check if block belongs to a page
        if (!$block->page) {
            return response()->json(['error' => 'Block does not belong to a page'], Response::HTTP_BAD_REQUEST);
        }

        // Check if the block has an outline
        if (!$block->outline) {
            return response()->json(['error' => 'Block does not have an outline'], Response::HTTP_BAD_REQUEST);
        }

        // Check if page belongs to a recommendation
        $recommendation = $block->page->recommendation;
        if (!$recommendation) {
            return response()->json(['error' => 'Page does not belong to a recommendation'], Response::HTTP_BAD_REQUEST);
        }

        // Update recommendation status
        $recommendation->update([
            'status' => 'page_builder_in_progress',
            // 'metadata' => array_merge($recommendation->metadata ?? [], [
            //     'retry_count' => 0,
            //     'regeneration_request' => true,
            // ])
        ]);

        // Dispatch the job to regenerate the block HTML
        BlockBuilderMagicPatterns::dispatch($recommendation, $block);

        // Return the block with updated status
        $block->update(['status' => 'regenerating']);
        
        return new BlockResource($block);
    }
}
