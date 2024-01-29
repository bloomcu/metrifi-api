<?php

namespace DDD\Domain\Pages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\App\Traits\HasSlug;
use DDD\App\Traits\HasParents;

class PageType extends Model
{
    use HasFactory,
        HasSlug,
        HasParents;

    protected $guarded = [
        'id',
    ];
}
