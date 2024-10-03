<?php

namespace DDD\Domain\Recommendations\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RecommendationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'dashboard_id' => $this->dashboard_id,
            'thread_id' => $this->thread_id,
            'runs' => $this->runs,
            'status' => $this->status,
            'title' => $this->title,
            'content' => $this->content,
            'prototype' => $this->prototype,
            'period' => $this->period,
            'reference' => $this->reference,
            'step_index' => $this->step_index,
            'prompt' => $this->prompt,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
