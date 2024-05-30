<?php

namespace DDD\Domain\Organizations\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class OnboardingCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        $value = isset($value) ? json_decode($value, true) : [];

        $defaults = [
            'connect-google-analytics' => 'incomplete',
            'enable-enhanced-measurement' => 'incomplete',
            'extend-data-retention-period' => 'incomplete',
            'setup-cross-domain-tracking' => 'incomplete',
            'filter-out-internal-traffic' => 'incomplete',
            'add-custom-dimensions' => 'incomplete',
            'onboardingComplete' => false,
            'hideOnboarding' => false,
        ];

        return array_merge($defaults, $value);

        // $onboardingSteps = [
        //     'connect-google-analytics' => [
        //         'slug' => 'connect-google-analytics',
        //         'complete' => false,
        //     ],
        //     'enable-enhanced-measurement' => [
        //         'slug' => 'enable-enhanced-measurement',
        //         'complete' => false,
        //     ],
        //     'extend-data-retention-period' => [
        //         'slug' => 'extend-data-retention-period',
        //         'complete' => false,
        //     ],
        //     'setup-cross-domain-tracking' => [
        //         'slug' => 'setup-cross-domain-tracking',
        //         'complete' => false,
        //     ],
        //     'add-custom-dimensions' => [
        //         'slug' => 'add-custom-dimensions',
        //         'complete' => false,
        //     ],
        //     'filter-out-internal-traffic' => [
        //         'slug' => 'filter-out-internal-traffic',
        //         'complete' => false,
        //     ],
        // ];

        // return collect(json_decode($value, true))->map(function ($step) use ($onboardingSteps) {
        //     $default = $onboardingSteps[$step['slug']];

        //     return array_merge($default, $step);
        // });
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if (isset($value)) {
            return json_encode($value);
        }
    }
}
