<?php

namespace DDD\Domain\Funnels;

use Illuminate\Support\Facades\Bus;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Organizations\Actions\CalculateOrganizationTotalAssetsAction;
use DDD\Domain\Funnels\Traits\IsOrderable;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\Casts\FunnelStepMetricsCast;
use DDD\Domain\Funnels\Actions\FunnelSnapshotAction;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Analyses\Actions\AnalyzeDashboardAction;
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
            $watchedFields = ['order', 'metrics', 'metrics_expression'];
            $changes = $funnelStep->getChanges();
            $significantChanges = array_intersect_key($changes, array_flip($watchedFields));
            
            // If there are changes in the watched fields
            if (!empty($significantChanges)) {
              // Snapshot the funnel
              FunnelSnapshotAction::dispatch($funnelStep->funnel, 'last28Days');

              // If funnel is the subject of any dashboards, analyze it then re-calculate org assets
              $dashboards = Dashboard::whereFocusFunnelId($funnelStep->funnel->id)->get();

              if ($dashboards->isNotEmpty()) {
                // Create array of jobs for each dashboard
                $dashboardJobs = $dashboards->map(function ($dashboard) {
                    return AnalyzeDashboardAction::makeJob($dashboard);
                })->all();
                
                Bus::chain([
                  ...$dashboardJobs,
                  CalculateOrganizationTotalAssetsAction::makeJob($funnelStep->funnel->organization),
                ])->dispatch();
              }
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
