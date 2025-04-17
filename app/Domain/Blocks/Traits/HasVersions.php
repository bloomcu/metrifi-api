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
        // Create initial version when a block is created
        static::created(function ($model) {
            $model->createInitialVersion();
        });
        
        // Create a new version when a block is updated
        static::updating(function ($model) {
            $model->createVersionFromChanges();
        });
    }
    
    /**
     * Create the initial version for a new block.
     */
    protected function createInitialVersion()
    {
        $this->versions()->create([
            'block_id' => $this->id,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'data' => $this->getAttributes(),
            'version_number' => 1,
        ]);
        
        // Set current version to 1
        $this->current_version = 1;
        $this->saveQuietly();
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
            // Find the highest version number and increment by 1
            $maxVersionNumber = $this->versions()->max('version_number') ?? 0;
            $newVersionNumber = $maxVersionNumber + 1;
            
            // We need to apply the changes to the attributes before creating the version
            $attributes = $this->getAttributes();
            foreach ($changes as $key => $value) {
                $attributes[$key] = $value;
            }
            
            $this->versions()->create([
                'block_id' => $this->id,
                'organization_id' => $this->organization_id,
                'user_id' => $this->user_id,
                'data' => $attributes,
                'version_number' => $newVersionNumber,
            ]);
            
            // Update the current version number
            $this->current_version = $newVersionNumber;
        }
    }

    /**
     * Revert to a specific version.
     */
    public function revertToVersion(BlockVersion $version)
    {
        // Ensure the version belongs to this block
        if ($version->block_id !== $this->id) {
            return false;
        }
        
        DB::transaction(function () use ($version) {
            // Update the block with the version's data
            $versionData = $version->data->toArray();
            
            // Only update versionable attributes
            foreach ($this->versionableAttributes as $attribute) {
                if (isset($versionData[$attribute])) {
                    $this->{$attribute} = $versionData[$attribute];
                }
            }
            
            // Update the current version to the target version number
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
