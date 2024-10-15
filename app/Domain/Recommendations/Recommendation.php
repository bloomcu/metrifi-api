<?php

namespace DDD\Domain\Recommendations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Files\File;
use DDD\Domain\Dashboards\Dashboard;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;

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
        // 'file_ids' => 'array',
    ];
    
    protected $attributes = [
        'runs' => '[]',
    ];

    public function dashboard()
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function files()
    {
        return $this->belongsToMany(File::class, 'recommendation_files');
    }
}
