<?php

namespace DDD\App\Console\Commands;

use Illuminate\Console\Command;
use DDD\Domain\Users\User;
use DDD\Domain\Recommendations\Recommendation;

class SyncRecommendationOrgs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recommendations:sync-orgs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add org id to recommendations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $recommendations = Recommendation::all();

        // Loop through each recommendation, get the organization id from the reccomendation's dashboard and save it to the recommendation
        foreach ($recommendations as $recommendation) {
            $organization_id = $recommendation->dashboard->organization_id;
            $recommendation->organization_id = $organization_id;
            $recommendation->save();
        }
    }
}
