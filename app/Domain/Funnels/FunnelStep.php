<?php

namespace DDD\Domain\Funnels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Funnels\Traits\IsOrderable;
use DDD\Domain\Funnels\Casts\FunnelStepMetricsCast;
use DDD\App\Traits\BelongsToFunnel;
use Illuminate\Database\Eloquent\SoftDeletes;

class FunnelStep extends Model
{
    use HasFactory,
        SoftDeletes,
        BelongsToFunnel,
        IsOrderable;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'metrics' => FunnelStepMetricsCast::class,
    ];
}
