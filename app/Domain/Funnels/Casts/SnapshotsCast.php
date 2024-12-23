<?php

namespace DDD\Domain\Funnels\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class SnapshotsCast implements CastsAttributes
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

        $defaultSnapshots = [
            'last28Days' => [
                'assets' => null,
                'conversion_rate' => null,
                'users' => null,
            ],
            'last90Days' => [
                'assets' => null,
                'conversion_rate' => null,
                'users' => null,
            ],
        ];

        return array_merge($defaultSnapshots, $value);
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
