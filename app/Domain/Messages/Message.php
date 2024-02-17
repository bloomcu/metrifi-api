<?php

namespace DDD\Domain\Messages;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\App\Traits\BelongsToFunnel;

class Message extends Model
{
    use HasFactory,
        SoftDeletes,
        BelongsToFunnel;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'json' => 'array',
    ];
}
