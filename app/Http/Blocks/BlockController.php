<?php

namespace DDD\Http\Blocks;

use Illuminate\Support\Facades\Log;
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
      'order' => 'nullable|integer',
      'status' => 'nullable|string',
      'error' => 'nullable|string',
      'title' => 'nullable|string',
      'type' => 'nullable|string',
      'layout' => 'nullable|string',
      'wordpress_category' => 'nullable|string',
      'html' => 'nullable|string',
    ]);

    // Log validate
    // Log::info('Validated block data: ' . json_encode($validated));

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
      'order' => 'nullable|integer',
      'status' => 'nullable|string',
      'error' => 'nullable|string',
      'title' => 'nullable|string',
      'type' => 'nullable|string',
      'layout' => 'nullable|string',
      'wordpress_category' => 'nullable|string',
      'outline' => 'nullable|string',
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
