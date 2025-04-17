<?php

namespace DDD\Domain\Blocks;

use Illuminate\Database\Eloquent\Model;
use DDD\Domain\Blocks\Block;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Casts\AsCollection;

class BlockVersion extends Model
{
    use BelongsToUser, BelongsToOrganization;

    protected $guarded = ['id'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => AsCollection::class,
    ];

    /**
     * The block this version belongs to
     */
    public function block()
    {
        return $this->belongsTo(Block::class);
    }
}
