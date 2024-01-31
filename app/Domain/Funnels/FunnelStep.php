<?php

namespace DDD\Domain\Funnels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Funnels\Traits\IsOrderable;
use DDD\Domain\Funnels\Casts\FunnelStepMeasurables;
use DDD\App\Traits\BelongsToFunnel;

class FunnelStep extends Model
{
    use HasFactory,
        BelongsToFunnel,
        IsOrderable;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        // 'measurables' => 'array',
        'measurables' => FunnelStepMeasurables::class,
    ];
}
