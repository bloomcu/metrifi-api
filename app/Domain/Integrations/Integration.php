<?php

namespace DDD\Domain\Integrations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;

class Integration extends Model
{
    use HasFactory,
        BelongsToOrganization,
        BelongsToUser;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'token' => 'json',
    ];
}
