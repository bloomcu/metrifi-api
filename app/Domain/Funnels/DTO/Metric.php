<?php

namespace DDD\Domain\Funnels\DTO;

class Metric
{
    public function __construct(
        public $connection_id = '',
        public $metric = 'pageViews',
        public $measurable = '',
    ) {}
}
