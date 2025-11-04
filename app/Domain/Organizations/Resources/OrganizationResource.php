<?php

namespace DDD\Domain\Organizations\Resources;

use Illuminate\Support\Carbon;
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
            'domain' => $this->domain,
            'is_private' => $this->is_private,
            'automating' => $this->automating,
            'automation_msg' => $this->automation_msg,
            'return_on_assets' => $this->return_on_assets,
            'onboarding' => $this->onboarding,
            'assets' => $this->assets,
            'recommendations_limit' => $this->recommendations_limit,
            'subscribed' => $this->subscribed('default'),
            'funnels_count' => $this->whenLoaded('funnels', function () {
                return $this->funnels->count();
            }),
            'connections_count' => $this->whenLoaded('connections', function () {
                return $this->connections->count();
            }),
            // 'ends_at' => optional(optional($this->subscription('default'))->ends_at)->toDateTimeString(),
            // 'subscription_started_at' => $this->when($this->subscribed('default'), function () {
            //     return Carbon::createFromTimeStamp($this->subscription('default')->asStripeSubscription()->current_period_start);
            // }),
            // 'subscription_renews_at' => $this->when($this->subscribed('default'), function () {
            //     return Carbon::createFromTimeStamp($this->subscription('default')->asStripeSubscription()->current_period_end);
            // }),
            // 'plan' => new PlanResource($this->plan),
            // 'created_at' => $this->created_at,
        ];
    }
}
