<?php

namespace DDD\App\Console\Commands;

use Illuminate\Console\Command;
use DDD\Domain\Blocks\Block;
use DDD\Domain\Blocks\BlockVersion;

class GenerateBlockVersions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blocks:generate-versions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate initial versions for all blocks that do not have versions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Generating versions for blocks...');
        
        // Get all blocks
        $blocks = Block::all();
        
        $this->info("Found {$blocks->count()} blocks");
        
        $processed = 0;
        $created = 0;
        
        foreach ($blocks as $block) {
            $processed++;
            
            // Check if the block already has versions
            $hasVersions = BlockVersion::where('block_id', $block->id)->exists();
            
            if (!$hasVersions) {
                // Create initial version
                BlockVersion::create([
                    'block_id' => $block->id,
                    'organization_id' => $block->organization_id,
                    'user_id' => $block->user_id,
                    'data' => $block->getAttributes(),
                    'version_number' => 1,
                ]);
                
                // Set current version to 1
                $block->current_version = 1;
                $block->saveQuietly();
                
                $created++;
            }
            
            if ($processed % 100 === 0) {
                $this->info("Processed {$processed} blocks...");
            }
        }
        
        $this->info("Completed! Created versions for {$created} blocks out of {$processed} total blocks.");
        
        return 0;
    }
}
