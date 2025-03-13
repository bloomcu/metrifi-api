<?php

namespace DDD\Domain\Blocks\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use DDD\Domain\Users\Resources\UserResource;

class BlockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => ['name' => $this->user->name],
            'order' => $this->order,
            'title' => $this->title,
            'type' => $this->type,
            'variant' => $this->variant,
            'html' => $this->html,
        ];
    }
}
