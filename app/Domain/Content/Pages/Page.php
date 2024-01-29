<?php

namespace DDD\Domain\Pages;

use DDD\App\Traits\BelongsToConnection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Pages\PageType;
use DDD\Domain\Connections\Connection;

class Page extends Model
{
    use HasFactory,
        SoftDeletes,
        BelongsToConnection;

    protected $guarded = [
        'id',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::saving(function (Model $page) {
            if (request()->type) {
                $page->type()->associate(
                    PageType::firstWhere('slug', request()->type)
                );
            }
        });
    }

    public function type()
    {
        return $this->belongsTo(PageType::class);
    }
}
