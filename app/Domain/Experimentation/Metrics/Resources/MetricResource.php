<?php

namespace DDD\Domain\Metrics\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MetricResource extends JsonResource
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
            'title' => $this->title, // title of the metric (standard names will be Visit, Click, Form Submission, etc)
            'type' => $this->type, // visit, click, submission, scroll, bounce, etc
            'conversions' => $this->conversions, // total actions we wanted taken (leads)
            'visits' => $this->visits, // total events (visits)
            'conversion_rate' => $this->conversion_rate, // 34%
        ];
    }
}
