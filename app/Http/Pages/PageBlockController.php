<?php

namespace DDD\Http\Pages;

use Illuminate\Http\Request;
use DDD\Domain\Pages\Page;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Blocks\Resources\BlockResource;
use DDD\Domain\Blocks\Block;
use DDD\App\Controllers\Controller;

class PageBlockController extends Controller
{
  public function store(Organization $organization, Page $page, Request $request)
  {
    $validated = $request->validate([
      'order' => 'nullable|string',
      'title' => 'nullable|string',
      'type' => 'nullable|string',
      'variant' => 'nullable|string',
      'html' => 'nullable|string',
    ]);

    $block = $organization->blocks()->create([
        'page_id' => $page->id,
        ...$validated
    ]);

    return new BlockResource($block);
  }

  public function show(Organization $organization, Page $page, Block $block)
  {
      return new BlockResource($block);
  }

  public function update(Organization $organization, Page $page, Block $block, Request $request)
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

  public function destroy(Organization $organization, Page $page, Block $block)
  {
    $block->delete();

    return new BlockResource($block);
  }
}
