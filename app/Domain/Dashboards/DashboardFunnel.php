<?php

namespace DDD\Domain\Dashboards;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Analyses\Analysis;
use DDD\Domain\Dashboards\Traits\DashboardFunnelIsOrderable;

class DashboardFunnel extends Model
{
    use HasFactory,
        DashboardFunnelIsOrderable;

    protected $guarded = [
        'id',
    ];
}
