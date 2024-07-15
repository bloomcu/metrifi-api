<?php

namespace DDD\Domain\Dashboards\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardFunnelResource extends JsonResource
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
            'order' => $this->order,
            // 'disabled_steps' => $this->disabled_steps ? $this->disabled_steps : [],
            'disabled_steps' => $this->disabled_steps ? json_decode($this->disabled_steps, true) : [],
        ];
    }
}
