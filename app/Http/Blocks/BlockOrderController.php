<?php

namespace DDD\Http\Blocks;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Blocks\Resources\BlockResource;
use DDD\Domain\Blocks\Block;
use DDD\App\Controllers\Controller;

class BlockOrderController extends Controller
{
    public function reorder(Organization $organization, Block $block, Request $request)
    {
        $request->validate([
            'order' => 'required|integer',
        ]);

        $block->reorder($request->order);

        return new BlockResource($block);
    }
}
