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
            'in_progress' => $this->in_progress,
            'title' => $this->title,
            'content' => $this->content,
            'prototype' => $this->prototype,
            'period' => $this->period,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
