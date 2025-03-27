<?php

namespace DDD\Http\Pages;

use Illuminate\Http\Request;
use DDD\Domain\Pages\Resources\PageResource;
use DDD\Domain\Pages\Page;
use DDD\Domain\Organizations\Organization;
use DDD\App\Controllers\Controller;

class PageController extends Controller
{
  public function store(Organization $organization, Request $request)
  {
    $validated = $request->validate([
      'recommendation_id' => 'nullable|integer',
      'title' => 'required|string',
      'url' => 'nullable|string',
    ]);

    $page = $organization->pages()->create($validated);

    return new PageResource($page);
  }

  public function show(Organization $organization, Page $page)
  {
      return new PageResource($page);
  }

  public function update(Organization $organization, Page $page, Request $request)
  {        
    $validated = $request->validate([
      'title' => 'nullable|string',
      'url' => 'nullable|string',
    ]);

    $page->update($validated);

    return new PageResource($page);
  }

  public function destroy(Organization $organization, Page $page)
  {
    $page->delete();

    return new PageResource($page);
  }
}
