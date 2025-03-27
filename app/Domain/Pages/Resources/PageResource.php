<?php

namespace DDD\Domain\Pages\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use DDD\Domain\Blocks\Resources\BlockResource;

class PageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recommendation_id' => $this->recommendation_id,
            'user' => ['name' => $this->user->name],
            'title' => $this->title,
            'url' => $this->url,
            'blocks' => BlockResource::collection($this->blocks),
        ];
    }
}
