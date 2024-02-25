<?php

namespace DDD\Domain\Organizations\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationPublicResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
        ];
    }
}
