<?php

namespace DDD\Domain\Funnels\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class FunnelStepMetricsCast implements CastsAttributes
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
        $defaultMetricAttributes = [
            'pageUsers' => [
                'metric' => 'pageUsers',
                'pagePath' => null,
                'hostname' => null,
                'matchType' => 'EXACT',
            ],
            'pagePlusQueryStringUsers' => [
                'metric' => 'pagePlusQueryStringUsers',
                'pagePathPlusQueryString' => null,
                'hostname' => null,
                'matchType' => 'EXACT',
            ],
            'pageTitleUsers' => [
                'metric' => 'pageTitleUsers',
                'pageTitle' => null,
                'hostname' => null,
                'matchType' => 'EXACT',
            ],
            'outboundLinkUsers' => [
                'metric' => 'outboundLinkUsers',
                'pagePath' => null,
                'linkUrl' => null,
                'hostname' => null,
                'matchType' => 'EXACT',
                'linkUrlMatchType' => 'EXACT',
                'pagePathMatchType' => 'EXACT',
            ],
            'formUserSubmissions' => [
                'metric' => 'formUserSubmissions',
                'pagePath' => null,
                'formDestination' => null,
                'formId' => null,
                'formLength' => null,
                'formSubmitText' => null,
                'hostname' => null,
                'matchType' => 'EXACT',
                'pagePathMatchType' => 'EXACT',
            ],
        ];

        return collect(json_decode($value, true))->map(function ($metric) use ($defaultMetricAttributes) {
            $defaults = $defaultMetricAttributes[$metric['metric']] ?? [];

            $mergedMetric = array_merge($defaults, $metric);
            if (!isset($mergedMetric['matchType'])) {
                $mergedMetric['matchType'] = 'EXACT';
            }

            return $mergedMetric;
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
