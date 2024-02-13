<?php

namespace DDD\Domain\Funnels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Funnels\FunnelStep;
use DDD\Domain\Messages\Message;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;
use DDD\App\Traits\BelongsToConnection;
use Illuminate\Database\Eloquent\SoftDeletes;

class Funnel extends Model
{
    use HasFactory,
        SoftDeletes,
        BelongsToOrganization,
        BelongsToUser,
        BelongsToConnection;

    protected $guarded = [
        'id',
    ];

    public static function boot ()
    {
        parent::boot();

        self::deleting(function (Funnel $funnel) {
            $funnel->steps()->delete();
            $funnel->messages()->delete();
        });
    }

    /**
     * Steps associated with the funnel.
     *
     * @return hasMany
     */
    public function steps()
    {
        return $this->hasMany(FunnelStep::class)->orderBy('order');
    }

    /**
     * Messages associated with the funnel.
     *
     * @return hasMany
     */
    public function messages()
    {
        return $this->hasMany(Message::class)->latest();
    }
}
