<?php

namespace DDD\Domain\Dashboards;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Analyses\Analysis;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;
use DDD\Domain\Dashboards\Traits\DashboardIsOrderable;

class Dashboard extends Model
{
    use HasFactory,
        SoftDeletes,
        BelongsToOrganization,
        BelongsToUser,
        DashboardIsOrderable;

    protected $guarded = [
        'id',
    ];

    /**
     * Funnels associated with the dashboard.
     *
     * @return BelongsToMany
     */
    public function funnels()
    {
        // Private organization cannot see other funnels
        if ($this->organization->is_private) {
            return $this->belongsToMany(Funnel::class)
                ->where('organization_id', $this->organization->id) // Only return funnels from the same organization
                ->withPivot(['order', 'disabled_steps'])
                ->orderBy('order')
                ->withTimestamps();

        } else {
            return $this->belongsToMany(Funnel::class)
                ->whereRelation('organization', 'is_private', false) // Only return anonymous funnels
                ->withPivot(['order', 'disabled_steps'])
                ->orderBy('order')
                ->withTimestamps();
        }
    }

    /**
     * Analyses associated with the dashboard.
     *
     * @return HasMany
     */
    public function analyses()
    {
        return $this->hasMany(Analysis::class);
    }

    /**
     * Latest analyses associated with the dashboard.
     *
     * @return HasOne
     */
    public function latestAnalysis()
    {
        return $this->hasOne(Analysis::class)->latestOfMany();
    }
}
