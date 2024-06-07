<?php

namespace DDD\Domain\Benchmarks\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use DDD\Domain\Base\Categories\Resources\CategoryResource;

class BenchmarkResource extends JsonResource
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
            'category' => new CategoryResource($this->category),
            'name' => $this->name,
            'median' => $this->median,
            'bottom' => $this->bottom,
            'top' => $this->top,
            'count' => $this->count,
            'funnels' => $this->funnels,
            'calculated_at' => $this->calculated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
