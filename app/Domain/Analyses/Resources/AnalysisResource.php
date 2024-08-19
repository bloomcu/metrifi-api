<?php

namespace DDD\Domain\Analyses\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Dashboards\Resources\DashboardResource;
use DDD\Domain\Analyses\Enums\AnalysisIssueEnum;

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
            'issue' => $this->issue,
            'subject_funnel_conversion_value' => $this->subject_funnel_conversion_value,
            'subject_funnel_users' => $this->subject_funnel_users,
            'subject_funnel_performance' => $this->subject_funnel_performance,
            'bofi_step_index' => $this->bofi_step_index,
            'bofi_performance' => $this->bofi_performance,
            'bofi_conversion_rate' => $this->bofi_conversion_rate,
            'bofi_median_of_comparisons' => $this->bofi_median_of_comparisons,
            'bofi_asset_change' => $this->bofi_asset_change,
            // 'meta' => $this->meta,
            'reference' => $this->reference,
            // 'subject_funnel' => new FunnelResource($this->subjectFunnel),
            // 'dashboard' => new DashboardResource($this->dashboard),
            'period' => $this->period,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
