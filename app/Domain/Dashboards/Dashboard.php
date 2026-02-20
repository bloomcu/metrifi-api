<?php

namespace DDD\Domain\Dashboards;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Database\Query\Builder;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Dashboards\Traits\DashboardIsOrderable;
use DDD\Domain\Analyses\Analysis;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;

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
     * All funnels associated with the dashboard (no privacy filter).
     * Use for admin counts and when organization context is not available during query building.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function allFunnels()
    {
        return $this->belongsToMany(Funnel::class)
            ->withPivot(['order', 'disabled_steps', 'issue'])
            ->orderBy('order')
            ->withTimestamps();
    }

    /**
     * Funnels associated with the dashboard.
     *
     * @return BelongsToMany
     */
    public function funnels()
    {
        // Private organization cannot see other funnels
        if ($this->organization?->is_private) {
            return $this->belongsToMany(Funnel::class)
                ->where('organization_id', $this->organization->id) // Only return funnels from the same organization
                ->withPivot(['order', 'disabled_steps', 'issue'])
                ->orderBy('order')
                ->withTimestamps();
        }
        
        return $this->belongsToMany(Funnel::class)
          ->whereRelation('organization', 'is_private', false) // Only return anonymous funnels
          ->withPivot(['order', 'disabled_steps', 'issue'])
          ->orderBy('order')
          ->withTimestamps();
    }

    /**
   * Get the first funnel associated with the dashboard (order = 1).
   *
   * @return Funnel|null
   */
    public function focusFunnel()
    {
      return $this->funnels()->wherePivot('order', 1)->first();
    }

    /**
     * Scope to filter dashboards by their first funnel's ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $funnelId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereFocusFunnelId($query, $funnelId)
    {
        return $query->whereHas('funnels', function ($query) use ($funnelId) {
            $query->where('funnel_id', $funnelId)
                  ->where('dashboard_funnel.order', 1); // Constrain to order = 1
        });
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

    public function medianAnalysis(): HasOne
    {
        return $this->hasOne(Analysis::class)->ofMany(
            ['created_at' => 'max'],
            fn ($query) => $query->where('type', 'median')
        );
    }

    public function maxAnalysis(): HasOne
    {
        return $this->hasOne(Analysis::class)->ofMany(
            ['created_at' => 'max'],
            fn ($query) => $query->where('type', 'max')
        );
    }

    /**
     * Recommendations associated with the dashboard.
     *
     * @return HasMany
     */
    public function recommendations()
    {
        return $this->hasMany(Recommendation::class);
    }

    public function recommendation(): HasOne
    {
        return $this->hasOne(Recommendation::class)->latest();
    }
}
