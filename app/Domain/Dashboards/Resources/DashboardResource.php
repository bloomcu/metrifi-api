<?php

namespace DDD\Domain\Dashboards\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use DDD\Domain\Users\Resources\UserResource;
use DDD\Domain\Organizations\Resources\OrganizationResource;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Analyses\Resources\AnalysisResource;

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
            'order' => $this->order,
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'user' => new UserResource($this->whenLoaded('user')),
            'name' => $this->name,
            'analysis_in_progress' => $this->analysis_in_progress,
            'subject_funnel_performance' => $this->subject_funnel_performance,
            'latest_analysis' => new AnalysisResource($this->latestAnalysis),
            'latest_analysis' => $this->latestAnalysis,
            'notes' => $this->notes,
            'description' => $this->description,
            'zoom' => $this->zoom,
            'funnels' => FunnelResource::collection($this->whenLoaded('funnels')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
