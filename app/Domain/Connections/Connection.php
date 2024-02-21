<?php

namespace DDD\Domain\Connections;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;

class Connection extends Model
{
    use HasFactory,
        BelongsToOrganization,
        BelongsToUser;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'token' => 'json',
    ];

    /**
     * Funnels associated with the connection.
     *
     * @return hasMany
     */
    public function funnels()
    {
        return $this->hasMany(Funnel::class);
    }
}
