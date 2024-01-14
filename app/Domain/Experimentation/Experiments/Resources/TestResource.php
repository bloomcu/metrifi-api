<?php

namespace DDD\Domain\Tests\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TestResource extends JsonResource
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
            'organization_id' => $this->organization_id,
            'title' => $this->title, // Auto loan rates test
            // 'content_type' => $this->content_type, 
            'category' => $this->category, // hasOne
            'products' => $this->products, // hasOne
            'variations' => $this->variations, // hasMany (control, variation a)
            'impact' => $this->impact, // 34.14%
            'direction' => $this->direction, // positive, negative, neutral
            'confidence' => $this->confidence, // 99.99%
        ];
    }
}
