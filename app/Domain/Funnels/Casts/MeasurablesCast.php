<?php

namespace DDD\Domain\Funnels\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MeasurablesCast implements CastsAttributes
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
        return collect(json_decode($value, true))->map(function ($metric) {
            $metricAttributes = [
                'pageUsers' => [
                    'metric' => 'pageUsers',
                    'pagePath' => null,
                ],
                'pagePlusQueryStringUsers' => [
                    'metric' => 'pagePlusQueryStringUsers',
                    'pagePathPlusQueryString' => null,
                ],
                'outboundLinkUsers' => [
                    'metric' => 'outboundLinkUsers',
                    'sourcePagePath' => null,
                    'linkUrl' => null,
                ],
            ];

            $defaults = $metricAttributes[$metric['metric']];

            return array_merge($defaults, $metric);
        });
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
