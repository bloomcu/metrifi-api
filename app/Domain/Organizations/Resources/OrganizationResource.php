<?php

namespace DDD\Domain\Organizations\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'automating' => $this->automating,
            'automation_msg' => $this->automation_msg,
            'created_at' => $this->created_at,
        ];
    }
}
