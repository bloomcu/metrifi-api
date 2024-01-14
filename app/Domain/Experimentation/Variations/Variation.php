<?php

namespace DDD\Domain\Variations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Traits
use DDD\App\Traits\BelongsToOrganization;

class Variation extends Model
{
    use HasFactory,
        // SoftDeletes,
        BelongsToOrganization;

    protected $guarded = [
        'id',
    ];
}
