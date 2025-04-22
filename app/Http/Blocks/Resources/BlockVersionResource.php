<?php

namespace DDD\Http\Blocks\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BlockVersionResource extends JsonResource
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
            'block_id' => $this->block_id,
            'version_number' => $this->version_number,
            'data' => $this->data,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
