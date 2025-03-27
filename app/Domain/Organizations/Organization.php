<?php

namespace DDD\Domain\Organizations;

// Domains
use Laravel\Cashier\Subscription;
use Laravel\Cashier\Billable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use DDD\Domain\Users\User;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Pages\Page;
use DDD\Domain\Organizations\Casts\OnboardingCast;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Files\File;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Connections\Connection;
use DDD\Domain\Blocks\Block;
use DDD\Domain\Base\Teams\Team;
use DDD\Domain\Base\Subscriptions\Plans\Plan;
use DDD\Domain\Base\Invitations\Invitation;
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
        'assets' => 'array',
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
    
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function plan()
    {
        return $this->hasOneThrough(
            Plan::class, Subscription::class,
            'organization_id', 'stripe_price_id', 'id', 'stripe_price'
        )
            ->where('stripe_status', '=', 'active')
            ->withDefault(Plan::free()->toArray());
    }

    public function connections()
    {
        return $this->hasMany(Connection::class);
    }

    public function funnels()
    {
        return $this->hasMany(Funnel::class)->latest();
    }

    public function dashboards()
    {
        return $this->hasMany(Dashboard::class)->orderBy('order');
    }

    public function recommendations()
    {
        return $this->hasMany(Recommendation::class);
    }

    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    public function blocks()
    {
        return $this->hasMany(Block::class);
    }

    /**
     * Set the domain attribute.
     *
     * @param  string  $value
     * @return void
     */
    public function setDomainAttribute($value)
    {
        // Parse the URL to get the host part
        $parsedUrl = parse_url($value, PHP_URL_HOST);

        // If no host is found, try with the path (in cases where the user enters 'example.com' without http(s)://)
        if (!$parsedUrl) {
            $parsedUrl = parse_url('http://' . $value, PHP_URL_HOST);
        }

        // Remove any trailing slash or path segments
        $this->attributes['domain'] = preg_replace('/\/.*$/', '', $parsedUrl);
    }
}
