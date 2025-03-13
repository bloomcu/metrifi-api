<?php

namespace DDD\Domain\Recommendations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Files\File;
use DDD\Domain\Dashboards\Dashboard;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;
use DDD\Domain\Pages\Page;

class Recommendation extends Model
{
    use HasFactory,
        BelongsToOrganization,
        BelongsToUser;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'runs' => 'array',
        'metadata' => 'array',
    ];
    
    protected $attributes = [
        'runs' => '[]',
    ];

    /**
     * Dashboard this recommendation belongs to.
     * 
     * @return belongsTo
     */
    public function dashboard()
    {
        return $this->belongsTo(Dashboard::class);
    }

    /**
     * Files that belong to this recommendation.
     *
     * @return belongsToMany
     */
    public function files()
    {
        return $this->belongsToMany(File::class, 'recommendation_files')->withPivot('type');
    }

    /**
     * Pages associated with this recommendation.
     *
     * @return hasMany
     */
    public function pages()
    {
        return $this->hasMany(Page::class)->orderBy('created_at');
    }
}
