<?php

namespace DDD\Domain\Dashboards;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;

class Dashboard extends Model
{
    use HasFactory,
        SoftDeletes,
        BelongsToOrganization,
        BelongsToUser;

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
        // return $this->belongsToMany(Funnel::class)->orderBy('order');
        

        // Private organization cannot see other funnels
        if ($this->organization->is_private) {
            return $this->belongsToMany(Funnel::class)
                ->where('organization_id', $this->organization->id) // Only return funnels from the same organization
                ->withTimestamps();

        } else {
            return $this->belongsToMany(Funnel::class)
                ->whereRelation('organization', 'is_private', false) // Only return anonymous funnels
                ->withTimestamps();
        }
    }
}
