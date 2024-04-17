<?php

namespace DDD\Domain\Connections;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;

class Connection extends Model
{
    use HasFactory,
        SoftDeletes,
        CascadeSoftDeletes,
        BelongsToOrganization,
        BelongsToUser;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'token' => 'json',
    ];

    protected $cascadeDeletes = ['funnels'];

    // public static function boot()
    // {
    //     parent::boot();

    //     self::deleting(function (Connection $connection) {
    //         foreach ($connection->funnels as $funnel) {
    //             $funnel->cascadeDelete();
    //         }
    //     });
    // }

    /**
     * Funnels associated with the connection.
     *
     * @return HasMany
     */
    public function funnels()
    {
        return $this->hasMany(Funnel::class);
    }
}
