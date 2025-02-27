<?php

namespace DDD\Domain\Users\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'settings' => $this->settings,
            'created_at' => $this->created_at,
            'accepted_terms_at' => $this->accepted_terms_at,
        ];
    }
}
