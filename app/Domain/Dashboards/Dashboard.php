<?php

namespace DDD\Domain\Dashboards;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;

class Dashboard extends Model
{
    use HasFactory,
        SoftDeletes,
        BelongsToOrganization,
        BelongsToUser;

    protected $guarded = [
        'id',
    ];

    // public static function boot ()
    // {
    //     parent::boot();

    //     self::updating(function (Dashboard $dashboard) {
    //         if (request()->attachFunnels) {
    //             $dashboard->funnels(request()->attachFunnels);
    //         }
    //     });
    // }

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
