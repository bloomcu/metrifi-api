<?php

namespace DDD\Domain\Recommendations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Dashboards\Dashboard;
use DDD\App\Traits\BelongsToOrganization;

class Recommendation extends Model
{
    use HasFactory,
        BelongsToOrganization;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'runs' => 'array',
    ];

    protected $attributes = [
        'runs' => '[]',
    ];

    /**
     * Dashboard this recommendation belongs to.
     *
     * @return BelongsTo
     */
    public function dashboard()
    {
        return $this->belongsTo(Dashboard::class);
    }
}
