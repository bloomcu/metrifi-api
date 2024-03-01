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
            $defaults = [
                // 'connection_id' => null,
                'metric' => 'pageViews',
                'pagePath' => null,
                'measurable' => null,
                // 'contains' => [],
            ];

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
