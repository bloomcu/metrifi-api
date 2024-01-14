<?php

namespace DDD\Domain\Variations\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VariationResource extends JsonResource
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
            'title' => $this->title, // string, control, variation a, variation b
            'winner' => $this->winner, // boolean, true/false
            'modification' => $this->modification, // number of modifications made
            'metrics' => $this->metrics, //  hasMany
        ];
    }
}
