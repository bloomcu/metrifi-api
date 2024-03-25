<?php

namespace DDD\Domain\Funnels\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use DDD\Domain\Organizations\Resources\OrganizationPublicResource;

class FunnelPublicResource extends JsonResource
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
            'organization' => new OrganizationPublicResource($this->organization),
            'name' => $this->name,
            'conversion_value' => $this->conversion_value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
