<?php

namespace DDD\Domain\Funnels;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use DDD\Domain\Messages\Message;
use DDD\Domain\Funnels\FunnelStep;
use DDD\Domain\Funnels\Casts\SnapshotsCast;
use DDD\Domain\Funnels\Casts\ProjectionsCast;
use DDD\Domain\Funnels\Actions\FunnelSnapshotAction;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Base\Categories\Category;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;
use DDD\App\Traits\BelongsToConnection;

class Funnel extends Model
{
    use HasFactory,
        SoftDeletes,
        CascadeSoftDeletes,
        Searchable,
        BelongsToOrganization,
        BelongsToUser,
        BelongsToConnection;

    protected $guarded = [
        'id',
    ];

    protected $cascadeDeletes = ['steps', 'messages'];

    protected $casts = [
        'snapshots' => SnapshotsCast::class,
        'projections' => ProjectionsCast::class,
    ];

    protected static function booted()
    {
        static::updated(function ($funnel) {
            // Define the fields that should trigger the job when changed
            $watchedFields = ['conversion_value'];
            
            // Get the changed attributes
            $changes = $funnel->getChanges();
            
            // Check if any of the watched fields have changed
            $significantChanges = array_intersect_key($changes, array_flip($watchedFields));
            
            // If there are changes in the watched fields, dispatch the job
            if (!empty($significantChanges)) {
                FunnelSnapshotAction::dispatch($funnel, 'last28Days');
            }
        });
    }

    /**
     * Steps associated with the funnel.
     *
     * @return HasMany
     */
    public function steps()
    {
        return $this->hasMany(FunnelStep::class)->orderBy('order');
    }

    /**
     * Messages associated with the funnel.
     *
     * @return HasMany
     */
    public function messages()
    {
        return $this->hasMany(Message::class)->latest();
    }

    /**
     * Dashboards this funnel is associated with.
     *
     * @return BelongsToMany
     */
    public function dashboards()
    {
        return $this->belongsToMany(Dashboard::class);
    }

    /**
     * Category this funnel belongs to.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
