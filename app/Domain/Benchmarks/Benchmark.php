<?php

namespace DDD\Domain\Benchmarks;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Base\Categories\Category;

class Benchmark extends Model
{
    use HasFactory,
        SoftDeletes,
        Searchable;

    protected $guarded = [
        'id',
    ];

    // protected $casts = [
    //     'snapshots' => SnapshotsCast::class,
    //     'projections' => ProjectionsCast::class,
    // ];

    // /**
    //  * Get the indexable data array for the model.
    //  *
    //  * @return array<string, mixed>
    //  */
    // public function toSearchableArray(): array
    // {
    //     return [
    //         'id' => (int) $this->id,
    //         'organization_id' => (int) $this->organization_id,
    //         'name' => $this->name,
    //         'created_at' => $this->created_at,
    //         'updated_at' => $this->updated_at,
    //     ];
    // }

    /**
     * Category this funnel belongs to.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
