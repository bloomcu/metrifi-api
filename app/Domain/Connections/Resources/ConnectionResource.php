<?php

namespace DDD\Domain\Connections\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use DDD\Domain\Base\Users\Resources\UserResource;

class ConnectionResource extends JsonResource
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
            'service' => $this->service,
            'account_name' => $this->account_name,
            'name' => $this->name,
            'uid' => $this->uid,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
