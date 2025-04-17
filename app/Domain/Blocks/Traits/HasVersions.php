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
                'version_number' => $this->versions()->count() + 1,
            ]);
        }
    }

    /**
     * Get all versions of this block.
     */
    public function versions()
    {
        return $this->hasMany(BlockVersion::class)->orderBy('version_number', 'desc');
    }

    /**
     * Get the previous version of this block.
     */
    public function previousVersion()
    {
        return $this->versions()->first();
    }

    /**
     * Revert to the previous version.
     */
    public function revertToPrevious()
    {
        $previousVersion = $this->previousVersion();
        
        if (!$previousVersion) {
            return false;
        }
        
        return $this->revertToVersion($previousVersion);
    }

    /**
     * Advance to the next version.
     */
    public function advanceToNext()
    {
        $currentVersionNumber = $this->versions()->where('version_number', '>', 1)->min('version_number') ?? 1;
     
        $nextVersion = $this->versions()->where('version_number', '<', $currentVersionNumber)->orderBy('version_number', 'desc')->first();
        
        if (!$nextVersion) {
            return false;
        }
        
        return $this->revertToVersion($nextVersion);
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
    
    /**
     * Get the current version number.
     */
    public function getCurrentVersionNumber()
    {
        // Explain how this works
        // 1. Get the minimum version number that is greater than 1
        // 2. If no version number is found, return 1
        // 3. Return the minimum version number
        
        return $this->versions()->where('version_number', '>', 1)->min('version_number') ?? 1;
    }
}
