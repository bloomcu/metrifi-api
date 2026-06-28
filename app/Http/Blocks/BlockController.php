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
      'status' => 'nullable|string|max:64',
      'error' => 'nullable|string',
      'title' => 'nullable|string|max:255',
      'type' => 'nullable|string|max:64',
      'layout' => 'nullable|string|max:64',
      'wordpress_category' => 'nullable|string|max:128',
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
      'order' => 'nullable|integer',
      'status' => 'nullable|string|max:64',
      'error' => 'nullable|string',
      'title' => 'nullable|string|max:255',
      'type' => 'nullable|string|max:64',
      'layout' => 'nullable|string|max:64',
      'wordpress_category' => 'nullable|string|max:128',
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
