<?php

namespace DDD\Domain\Funnels\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class FunnelStepExpression implements CastsAttributes
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
            'type' => null, // e.g., 'view', 'event', 'outbound'
            'field_name' => null, // e.g., 'pageLocation', 'pagePath', 'pageReferrer', 'landingPage', 'linkUrl (outbound)'
            'field_operator' => null, // e.g., 'EXACT', 'BEGINS_WITH', 'ENDS_WITH', 'CONTAINS'
            'field_value' => null, // e.g., 'https://bloomcu.com/contact', 'some other value'
        ];

        return array_merge($defaults, $value);
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
