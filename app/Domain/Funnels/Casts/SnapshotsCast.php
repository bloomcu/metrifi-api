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
            'yesterday' => [
                // 'period' => 'yesterday',
                'conversionRate' => null,
                // 'change' => null,
            ],
            'last7Days' => [
                // 'period' => 'last7Days',
                'conversionRate' => null,
                // 'change' => null,
            ],
            'last28Days' => [
                // 'period' => 'last28Days',
                'conversionRate' => null,
                // 'change' => null,
            ],
        ];
        
        // return collect($defaultSnapshots)->map(function ($default) use ($value) {
        //     // dd($default);
        //     $period = $default['period'];

        //     if (!isset($value[$period])) {
        //         $value[$period] = $default;
        //         return;
        //     }

        //     return array_merge($default, json_decode($value[$period], true));
        // });
        
        // return collect($value)->map(function ($snapshot) use ($defaultSnapshots) {
            
        //     $defaults = $defaultSnapshots[$snapshot['period']];
            

        //     return array_merge($defaults, $snapshot);
        // });

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
