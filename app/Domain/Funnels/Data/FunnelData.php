<?php

namespace DDD\Domain\Funnels\Data;

use Illuminate\Support\Collection;

class FunnelData
{
    public Collection $steps;
    public $connection;
    public $conversion_value;
    public $report;

    public function __construct(
        Collection $steps = null,
        $connection = null,
        $conversion_value = null
    ) {
        $this->steps = $steps ?? collect();
        $this->connection = $connection;
        $this->conversion_value = $conversion_value;
        $this->report = null; // Initialize as null; will be populated by the service.
    }
}
