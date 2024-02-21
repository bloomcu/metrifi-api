<?php

namespace DDD\Domain\Experiments;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Traits
use DDD\App\Traits\BelongsToOrganization;

class Experiment extends Model
{
    use HasFactory,
        // SoftDeletes,
        BelongsToOrganization;

    protected $guarded = [
        'id',
    ];
}
