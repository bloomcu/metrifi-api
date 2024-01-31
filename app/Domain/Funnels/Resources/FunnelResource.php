<?php

namespace DDD\Domain\Funnels\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use DDD\Domain\Connections\Resources\ConnectionResource;
use DDD\Domain\Base\Users\Resources\UserResource;

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
            'user' => new UserResource($this->user),
            'connection' => new ConnectionResource($this->connection),
            'name' => $this->name,
            'description' => $this->description,
            'steps' => FunnelStepResource::collection($this->steps),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
