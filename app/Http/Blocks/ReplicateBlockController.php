<?php

namespace DDD\Http\Blocks;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Blocks\Resources\BlockResource;
use DDD\Domain\Blocks\Block;
use DDD\App\Controllers\Controller;

class ReplicateBlockController extends Controller
{
    public function replicate(Organization $organization, Block $block)
    {
        $clonedBlock = $block->replicate();
        $clonedBlock->save();
        $clonedBlock->reorder($block->order + 1);

        return new BlockResource($clonedBlock);
    }
}
