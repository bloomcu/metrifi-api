<?php

namespace DDD\Domain\Funnels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\App\Traits\BelongsToFunnel;
use DDD\Domain\Funnels\Traits\IsOrderable;

class FunnelStep extends Model
{
    use HasFactory,
        BelongsToFunnel,
        IsOrderable;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'expression' => 'json',
    ];
}
