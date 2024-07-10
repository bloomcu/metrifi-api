<?php

namespace DDD\Domain\Analyses;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Dashboards\Dashboard;
use DDD\App\Traits\BelongsToOrganization;

class Analysis extends Model
{
    use HasFactory,
        SoftDeletes,
        BelongsToOrganization;

    protected $guarded = [
        'id',
    ];

    /**
     * Dashboard this analysis belongs to.
     *
     * @return BelongsTo
     */
    public function dashboard()
    {
        return $this->belongsTo(Dashboard::class);
    }

    /**
     * Subject funnel this analysis belongs to.
     */
    public function subjectFunnel()
    {
        return $this->belongsTo(Funnel::class, 'subject_funnel_id');
    }
}
