<?php

namespace DDD\Domain\Pages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Blocks\Block;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;

class Page extends Model
{
    use HasFactory,
        BelongsToOrganization,
        BelongsToUser;

    protected $guarded = [
        'id',
    ];

    /**
     * Recommendation this model belongs to.
     * 
     * @return belongsTo
     */
    public function recommendation()
    {
        return $this->belongsTo(Recommendation::class);
    }

    /**
     * Blocks associated with this page.
     *
     * @return hasMany
     */
    public function blocks()
    {
        return $this->hasMany(Block::class)->orderBy('order');
    }
}
