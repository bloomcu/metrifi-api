<?php

namespace DDD\Domain\Connections;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;
use Illuminate\Support\Facades\Crypt;

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

    protected $cascadeDeletes = ['funnels'];

    /**
     * Funnels associated with the connection.
     *
     * @return HasMany
     */
    public function funnels()
    {
        return $this->hasMany(Funnel::class);
    }

    /**
     * Encrypt token json
     *
     * @param array $value
     */    
    public function setTokenAttribute($value)
    {
        $this->attributes['token'] = Crypt::encrypt(json_encode($value));
    }

    /**
     * Decrypt token json
     * 
     * @return array
     */
    public function getTokenAttribute($value)
    {
        return json_decode(Crypt::decrypt($value), true);
    }
}
