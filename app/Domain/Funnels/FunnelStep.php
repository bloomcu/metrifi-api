<?php

namespace DDD\Domain\Funnels;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Funnels\Traits\IsOrderable;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\Casts\FunnelStepMetricsCast;
use DDD\Domain\Funnels\Actions\FunnelSnapshotAction;
use DDD\App\Traits\BelongsToFunnel;

class FunnelStep extends Model
{
    use HasFactory,
        SoftDeletes,
        BelongsToFunnel,
        IsOrderable;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'metrics' => FunnelStepMetricsCast::class,
    ];

    /**
     * The relationships that should be touched on save.
     *
     * @var array
     */
    protected $touches = ['funnel'];

    protected static function booted()
    {
        static::updated(function ($funnelStep) {
            // Define the fields that should trigger the job when changed
            $watchedFields = ['order', 'metrics'];
            
            // Get the changed attributes
            $changes = $funnelStep->getChanges();
            
            // Check if any of the watched fields have changed
            $significantChanges = array_intersect_key($changes, array_flip($watchedFields));
            
            // If there are changes in the watched fields, dispatch the job
            if (!empty($significantChanges)) {
                FunnelSnapshotAction::dispatch($funnelStep->funnel, 'last28Days');
            }
        });
    }

    /**
     * Get the funnel that this step belongs to.
     */
    public function funnel()
    {
        return $this->belongsTo(Funnel::class);
    }
}
