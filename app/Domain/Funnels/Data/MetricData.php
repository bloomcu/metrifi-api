<?php

namespace DDD\Domain\Funnels\Data;

class MetricData
{
    public $metric;
    public array $attributes;

    public function __construct(
        $metric = null,
        array $attributes = []
    ) {
        $this->metric = $metric;
        $this->attributes = $attributes;
    }

    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }
}
