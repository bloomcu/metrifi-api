<?php

namespace DDD\Domain\Metrics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Traits
use DDD\App\Traits\BelongsToOrganization;

class Metric extends Model
{
    use HasFactory,
        // SoftDeletes,
        BelongsToOrganization;

    protected $guarded = [
        'id',
    ];
}
