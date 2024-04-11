<?php

namespace DDD\Domain\Dashboards;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;

class Dashboard extends Model
{
    use HasFactory,
        BelongsToOrganization,
        BelongsToUser;

    protected $guarded = [
        'id',
    ];

    public static function boot()
    {
        parent::boot();

        self::deleting(function (Dashboard $dashboard) {
            $dashboard->funnels()->detach();
        });
    }

    /**
     * Funnels associated with the dashboard.
     *
     * @return BelongsToMany
     */
    public function funnels()
    {
        // return $this->belongsToMany(Funnel::class)->orderBy('order');
        return $this->belongsToMany(Funnel::class)->withTimestamps();
    }
}
