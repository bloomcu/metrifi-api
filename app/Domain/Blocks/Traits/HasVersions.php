<?php

namespace DDD\Domain\Blocks\Traits;

use DDD\Domain\Blocks\BlockVersion;
use Illuminate\Support\Facades\DB;

trait HasVersions
{
    /**
     * The attributes that should trigger versioning when changed.
     */
    protected $versionableAttributes = ['html'];

    /**
     * Boot the trait.
     */
    public static function bootHasVersions()
    {
        static::updating(function ($model) {
            $model->createVersionFromChanges();
        });
    }

    /**
     * Get all versions of this block.
     */
    public function versions()
    {
        return $this->hasMany(BlockVersion::class)->orderBy('version_number', 'desc');
    }

    /**
     * Create a version if versionable attributes have changed.
     */
    protected function createVersionFromChanges()
    {
        $changes = $this->getDirty();
        
        // Check if any versionable attributes have changed
        $versionableChanges = array_intersect_key($changes, array_flip($this->versionableAttributes));
        
        if (count($versionableChanges) > 0) {
            // Serialize the current state of the model
            $data = $this->getOriginal();
            
            $this->versions()->create([
                'block_id' => $this->id,
                'organization_id' => $this->organization_id,
                'user_id' => $this->user_id,
                'data' => $data,
                'version_number' => $this->current_version,
            ]);
            
            // Update the current version number
            $this->current_version = $this->current_version + 1;
        }
    }

    /**
     * Revert to a specific version.
     */
    public function revertToVersion(BlockVersion $version)
    {
        DB::transaction(function () use ($version) {
            // Create a version of the current state before reverting
            $this->createVersionFromChanges();
            
            // Update the block with the version's data
            $versionData = $version->data->toArray();
            
            // Only update versionable attributes
            foreach ($this->versionableAttributes as $attribute) {
                if (isset($versionData[$attribute])) {
                    $this->{$attribute} = $versionData[$attribute];
                }
            }
            
            // Update the current version
            $this->current_version = $version->version_number;
            
            // Save without triggering another version
            $this->saveQuietly();
        });
        
        return true;
    }

    /**
     * Save the model without triggering versioning.
     */
    public function saveQuietly(array $options = [])
    {
        return static::withoutEvents(function () use ($options) {
            return $this->save($options);
        });
    }
}
