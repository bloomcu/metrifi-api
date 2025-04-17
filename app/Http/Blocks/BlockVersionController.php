<?php

namespace DDD\Http\Blocks;

use Illuminate\Routing\Controller;
use DDD\Http\Blocks\Resources\BlockVersionResource;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Blocks\Resources\BlockResource;
use DDD\Domain\Blocks\BlockVersion;
use DDD\Domain\Blocks\Block;

class BlockVersionController extends Controller
{
    /**
     * Display a listing of the block versions.
     */
    public function index(Organization $organization, Block $block)
    {   
        $versions = $block->versions()->paginate();
        
        return BlockVersionResource::collection($versions);
    }

    /**
     * Revert to the previous version.
     */
    public function revert(Organization $organization, Block $block)
    {   
        $success = $block->revertToPrevious();
        
        if (!$success) {
            return response()->json(['message' => 'No previous version found'], 404);
        }
        
        return new BlockResource($block);
    }

    /**
     * Advance to the next version.
     */
    public function advance(Organization $organization, Block $block)
    {   
        $success = $block->advanceToNext();
        
        if (!$success) {
            return response()->json(['message' => 'No next version found'], 404);
        }
        
        return new BlockResource($block);
    }

    /**
     * Revert to a specific version.
     */
    public function change(Organization $organization, Block $block, BlockVersion $version)
    {   
        // Ensure the version belongs to this block
        if ($version->block_id !== $block->id) {
            return response()->json(['message' => 'Version does not belong to this block'], 403);
        }
        
        $block->revertToVersion($version);
        
        return new BlockResource($block);
    }
}
