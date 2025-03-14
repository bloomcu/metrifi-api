<?php

namespace DDD\App\Console\Commands;

use Illuminate\Console\Command;
use DDD\Domain\Recommendations\Recommendation;

class ConvertPrototypesToPagesAndBlocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prototypes:convert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert recommendation prototypes html to new pages with blocks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        
    }
}
