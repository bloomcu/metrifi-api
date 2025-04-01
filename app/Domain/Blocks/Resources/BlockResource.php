<?php

namespace DDD\Domain\Blocks\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class BlockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization' => ['slug' => $this->organization->slug],
            'user' => ['name' => $this->user->name],
            'order' => $this->order,
            'status' => $this->status,
            'error' => $this->error,
            'title' => $this->title,
            'outline' => $this->outline,
            'type' => $this->type,
            'layout' => $this->layout,
            'wordpress_category' => $this->wordpress_category,
            'html' => $this->html,
        ];
    }
}
