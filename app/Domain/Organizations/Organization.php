<?php

namespace DDD\Domain\Organizations;

// Domains
use Dyrynda\Database\Support\CascadeSoftDeletes;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Connections\Connection;
use DDD\Domain\Base\Organizations\Organization as BaseOrganization;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends BaseOrganization {

    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['connections', 'funnels', 'dashboards'];

    public static function boot()
    {
        parent::boot();

        self::deleting(function (Organization $organization) {
            $organization->invitations()->delete();
            $organization->files()->delete();
            $organization->teams()->delete();
            $organization->users()->delete();
        });
    }

    /**
     * Connections associated with the organization.
     *
     * @return hasMany
     */
    public function connections()
    {
        return $this->hasMany(Connection::class);
    }

    /**
     * Funnels associated with the organization.
     *
     * @return hasMany
     */
    public function funnels()
    {
        return $this->hasMany(Funnel::class)->latest();
    }

    /**
     * Dashboards associated with the organization.
     *
     * @return hasMany
     */
    public function dashboards()
    {
        return $this->hasMany(Dashboard::class)->latest();
    }
}
