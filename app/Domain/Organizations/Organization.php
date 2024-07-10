<?php

namespace DDD\Domain\Organizations;

// Domains
use Laravel\Cashier\Subscription;
use Laravel\Cashier\Billable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use DDD\Domain\Users\User;
use DDD\Domain\Organizations\Casts\OnboardingCast;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Connections\Connection;
use DDD\Domain\Base\Teams\Team;
use DDD\Domain\Base\Subscriptions\Plans\Plan;
use DDD\Domain\Base\Invitations\Invitation;
use DDD\Domain\Base\Files\File;
use DDD\App\Traits\HasSlug;

class Organization extends Model {

    use Billable,
        HasFactory,
        HasSlug,
        SoftDeletes, 
        CascadeSoftDeletes;
    
    protected $guarded = ['id', 'slug'];

    protected $cascadeDeletes = ['connections', 'funnels', 'dashboards'];

    protected $casts = [
        'onboarding' => OnboardingCast::class,
    ];

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
     * Users associated with the organization.
     * 
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Invitations associated with the organization.
     * 
     * @return HasMany
     */
    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Files associated with the organization.
     * 
     * @return HasMany
     */
    public function files()
    {
        return $this->hasMany(File::class);
    }

    /**
     * Teams that belong to this team.
     * 
     * @return HasMany
     */
    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Plan organization is subscribed to.
     * 
     * @return HasOneThrough
     */
    public function plan()
    {
        return $this->hasOneThrough(
            Plan::class, Subscription::class,
            'organization_id', 'stripe_price_id', 'id', 'stripe_price'
        )
            ->whereNull('subscriptions.ends_at') // Not being cancelled
            ->withDefault(Plan::free()->toArray());
    }

    /**
     * Connections associated with the organization.
     *
     * @return HasMany
     */
    public function connections()
    {
        return $this->hasMany(Connection::class);
    }

    /**
     * Funnels associated with the organization.
     *
     * @return HasMany
     */
    public function funnels()
    {
        return $this->hasMany(Funnel::class)->latest();
    }

    /**
     * Dashboards associated with the organization.
     *
     * @return HasMany
     */
    public function dashboards()
    {
        return $this->hasMany(Dashboard::class)->orderBy('order');
    }
}
