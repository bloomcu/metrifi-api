<?php

namespace DDD\Domain\Dashboards;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Dashboards\Traits\DashboardFunnelIsOrderable;
use DDD\Domain\Dashboards\Casts\DashboardFunnelDisabledSteps;

class DashboardFunnel extends Model
{
    use HasFactory,
        DashboardFunnelIsOrderable;

    protected $guarded = [
        'id',
    ];

    // Todo: The table should be named 'dashboard_funnels' instead of 'dashboard_funnel'
    protected $table = 'dashboard_funnel';

    protected $casts = [
        'disabled_steps' => DashboardFunnelDisabledSteps::class,
    ];
}
