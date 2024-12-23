<?php

namespace DDD\Http\Admin\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use DDD\Domain\Organizations\Resources\OrganizationResource;
use DDD\Domain\Base\Categories\Resources\CategoryResource;

class AdminFunnelResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'organization' => new OrganizationResource($this->organization),
            'category' => new CategoryResource($this->category),
            'name' => $this->name,
            'conversion_value' => $this->conversion_value,
            'snapshots' => $this->snapshots,
            'steps_count' => $this->steps->count(),
            'messages' => $this->messages,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
