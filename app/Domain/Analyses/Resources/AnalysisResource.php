<?php

namespace DDD\Domain\Analyses\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
            'type' => $this->type,
            // 'issue' => $this->issue,
            'subject_funnel_conversion_value' => $this->subject_funnel_conversion_value,
            'subject_funnel_users' => $this->subject_funnel_users,
            'subject_funnel_performance' => $this->subject_funnel_performance,
            'subject_funnel_conversion_rate' => $this->subject_funnel_conversion_rate,
            'median_of_comparison_conversion_rates' => $this->median_of_comparison_conversion_rates,
            'bofi_step_index' => $this->bofi_step_index,
            'bofi_performance' => $this->bofi_performance,
            'bofi_conversion_rate' => $this->bofi_conversion_rate,
            'bofi_median_of_comparisons' => $this->bofi_median_of_comparisons,
            'bofi_asset_change' => $this->bofi_asset_change,
            'reference' => $this->reference,
            'period' => $this->period,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
