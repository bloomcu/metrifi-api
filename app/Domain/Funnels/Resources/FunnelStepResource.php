<?php

namespace DDD\Domain\Funnels\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FunnelStepResource extends JsonResource
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
            'order' => $this->order,
            'metric' => $this->metric, // pageView, outboundLinkClick, elementClick, formSubmission
            'name' => $this->name,
            'description' => $this->description,
            'measurables' => $this->measurables, // Pages, Links, Elements, Forms.
            'total' => '0',
        ];
    }
}
