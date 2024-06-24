<?php

namespace DDD\Domain\Organizations\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use DDD\Domain\Base\Subscriptions\Plans\Resources\PlanResource;

class OrganizationResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'is_private' => $this->is_private,
            'automating' => $this->automating,
            'automation_msg' => $this->automation_msg,
            'onboarding' => $this->onboarding,
            // 'subscribed' => $this->subscribed('default'),
            // 'ends_at' => optional(optional($this->subscription('default'))->ends_at)->toDateTimeString(),
            // 'plan' => new PlanResource($this->plan),
            // 'created_at' => $this->created_at,
        ];
    }
}
