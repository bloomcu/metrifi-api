<?php

namespace DDD\Domain\Recommendations\Resources;

use DDD\Domain\Files\File;
use Illuminate\Http\Resources\Json\JsonResource;
use DDD\Http\Files\Resources\FileResource;
use DDD\Domain\Users\Resources\UserResource;

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
            'user' => new UserResource($this->user),
            'files' => FileResource::collection($this->files),
            'thread_id' => $this->thread_id,
            'runs' => $this->runs,
            'status' => $this->status ?? 'queued',
            'title' => $this->title,
            'content' => $this->content,
            'prototype' => $this->prototype,
            'period' => $this->period,
            'reference' => $this->reference,
            'step_index' => $this->step_index,
            'prompt' => $this->prompt,
            // 'file_ids' => $this->file_ids,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
