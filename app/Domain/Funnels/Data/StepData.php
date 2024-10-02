<?php

namespace DDD\Domain\Funnels\Data;

use Illuminate\Support\Collection;

class StepData
{
    public $id;
    public $name;
    public Collection $metrics;

    public function __construct(
        $id = null,
        $name = null,
        Collection $metrics = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->metrics = $metrics ?? collect();
    }
}
