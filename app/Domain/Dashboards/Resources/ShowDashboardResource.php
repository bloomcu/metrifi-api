<?php

namespace DDD\Domain\Dashboards\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use DDD\Domain\Users\Resources\UserResource;
use DDD\Domain\Recommendations\Resources\RecommendationResource;
use DDD\Domain\Organizations\Resources\OrganizationResource;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Analyses\Resources\AnalysisResource;

class ShowDashboardResource extends JsonResource
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
            'organization' => new OrganizationResource($this->organization),
            'name' => $this->name,
            'analysis_in_progress' => $this->analysis_in_progress,
            'issue' => $this->issue,
            'warning' => $this->warning,
            'subject_funnel_performance' => $this->subject_funnel_performance,
            // 'latest_analysis' => new AnalysisResource($this->latestAnalysis),
            // 'latest_analysis' => $this->latestAnalysis,
            'median_analysis' => new AnalysisResource($this->medianAnalysis),
            'max_analysis' => new AnalysisResource($this->maxAnalysis),
            'recommendation' => new RecommendationResource($this->recommendation),
            'notes' => $this->notes,
            'description' => $this->description,
            'zoom' => $this->zoom,
            'funnels' => FunnelResource::collection($this->funnels),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
