<?php

namespace DDD\Domain\Funnels\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FunnelStepResource extends JsonResource
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
            'order' => $this->order,
            'name' => $this->name,
            'description' => $this->description,
            'expression' => $this->expression,
        ];
    }
}
