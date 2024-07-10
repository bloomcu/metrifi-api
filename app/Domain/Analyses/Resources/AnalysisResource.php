<?php

namespace DDD\Domain\Analyses\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Dashboards\Resources\DashboardResource;

class AnalysisResource extends JsonResource
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
            'in_progress' => $this->in_progress,
            'subject_funnel_performance' => $this->subject_funnel_performance,
            'content' => $this->content,
            // 'subject_funnel' => new FunnelResource($this->subjectFunnel),
            // 'dashboard' => new DashboardResource($this->dashboard),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
