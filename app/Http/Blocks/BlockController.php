<?php

namespace DDD\Http\Blocks;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Blocks\Resources\BlockResource;
use DDD\Domain\Blocks\Block;
use DDD\App\Controllers\Controller;

class BlockController extends Controller
{
  public function store(Organization $organization, Request $request)
  {
    $validated = $request->validate([
    'page_id' => 'nullable|exists:pages,id',
      'order' => 'nullable|string',
      'title' => 'nullable|string',
      'type' => 'nullable|string',
      'variant' => 'nullable|string',
      'html' => 'nullable|string',
    ]);

    $block = $organization->blocks()->create($validated);

    return new BlockResource($block);
  }

  public function show(Organization $organization, Block $block)
  {
      return new BlockResource($block);
  }

  public function update(Organization $organization, Block $block, Request $request)
  {        
    $validated = $request->validate([
      'order' => 'nullable|string',
      'title' => 'nullable|string',
      'type' => 'nullable|string',
      'variant' => 'nullable|string',
      'html' => 'nullable|string',
    ]);

    $block->update($validated);

    return new BlockResource($block);
  }

  public function destroy(Organization $organization, Block $block)
  {
    $block->delete();

    return new BlockResource($block);
  }
}
