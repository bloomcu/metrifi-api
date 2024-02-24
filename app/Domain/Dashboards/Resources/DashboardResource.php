<?php

namespace DDD\Domain\Dashboards\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use DDD\Domain\Organizations\Resources\OrganizationResource;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Base\Users\Resources\UserResource;

class DashboardResource extends JsonResource
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
            'organization' => new OrganizationResource($this->organization),
            'user' => new UserResource($this->user),
            'name' => $this->name,
            'description' => $this->description,
            'funnels' => FunnelResource::collection($this->funnels),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}