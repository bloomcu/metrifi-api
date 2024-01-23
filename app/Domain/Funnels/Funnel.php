<?php

namespace DDD\Domain\Funnels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Funnels\FunnelStep;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;
use DDD\App\Traits\BelongsToConnection;

class Funnel extends Model
{
    use HasFactory,
        BelongsToOrganization,
        BelongsToUser,
        BelongsToConnection;

    protected $guarded = [
        'id',
    ];

    /**
     * Steps associated with the funnel.
     *
     * @return hasMany
     */
    public function steps()
    {
        return $this->hasMany(FunnelStep::class);
    }
}
