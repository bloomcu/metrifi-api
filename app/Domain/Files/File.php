<?php

namespace DDD\Domain\Files;

// use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DDD\Domain\Recommendations\Recommendation;
use DDD\App\Traits\BelongsToUser;
use DDD\App\Traits\BelongsToOrganization;

class File extends Model
{
    use BelongsToOrganization,
        BelongsToUser,
        HasFactory;

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        self::deleted(function (File $file) {
            Storage::disk($file->disk)->delete($file->filename);
        });

        self::creating(function (File $file) {
            // $image = ImageManager::gd()->read($file->filename);
            // $image->scale(width: 2000);
            // $image->save($file->filename);
        });
    }

    public function getStorageUrl()
    {
        return config('cdn.cdn_url') . '/' . $this->filename;
    }

    public function recommendations()
    {
        return $this->belongsToMany(Recommendation::class, 'recommendation_files');
    }
}
