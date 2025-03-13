<?php

namespace DDD\Domain\Pages\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use DDD\Domain\Blocks\Resources\BlockResource;
use DDD\Domain\Users\Resources\UserResource;

class PageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => ['name' => $this->user->name],
            'title' => $this->title,
            'url' => $this->url,
            'blocks' => BlockResource::collection($this->blocks),
        ];
    }
}
