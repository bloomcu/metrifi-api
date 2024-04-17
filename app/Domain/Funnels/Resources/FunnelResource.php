<?php

namespace DDD\Domain\Funnels\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use DDD\Domain\Connections\Resources\ConnectionResource;
use DDD\Domain\Users\Resources\UserResource;
use DDD\Domain\Organizations\Resources\OrganizationResource;

class FunnelResource extends JsonResource
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
            'connection_id' => $this->connection_id,
            'connection' => new ConnectionResource($this->connection),
            'name' => $this->name,
            'zoom' => $this->zoom,
            'conversion_value' => $this->conversion_value,
            'projections' => $this->projections,
            'steps' => FunnelStepResource::collection($this->steps),
            'messages' => $this->messages,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
