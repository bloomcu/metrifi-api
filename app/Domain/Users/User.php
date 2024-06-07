<?php

namespace DDD\Domain\Users;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Connections\Connection;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Users\Enums\RoleEnum;
use DDD\Domain\Base\Files\File;

class User extends Authenticatable
{
    use HasApiTokens,
        HasFactory,
        Notifiable,
        SoftDeletes,
        CascadeSoftDeletes;

    protected $cascadeDeletes = ['connections', 'funnels', 'dashboards'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'role',            // TODO: Remove
        'organization_id', // TODO: Remove
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'role' => RoleEnum::class,
        'email_verified_at' => 'datetime',
        'accepted_terms_at' => 'datetime',
    ];

    /**
     * Organization user belongs to.
     *
     * @return BelongsTo
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Files that belong to the user.
     *
     * @return HasMany
     */
    public function files()
    {
        return $this->hasMany(file::class);
    }

    /**
     * Connections that belong to the user.
     *
     * @return HasMany
     */
    public function connections()
    {
        return $this->hasMany(Connection::class);
    }

    /**
     * Funnels that belong to the user.
     *
     * @return HasMany
     */
    public function funnels()
    {
        return $this->hasMany(Funnel::class);
    }

    /**
     * Dashboards that belong to the user.
     *
     * @return HasMany
     */
    public function dashboards()
    {
        return $this->hasMany(Dashboard::class);
    }
}
