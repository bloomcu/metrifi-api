<?php

namespace DDD\Domain\Connections;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;

class Connection extends Model
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
