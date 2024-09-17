<?php

namespace DDD\Domain\Recommendations\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Recommendations\Resources\RecommendationResource;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Organizations\Actions\CalculateOrganizationTotalAssetsAction;
use DDD\Domain\Dashboards\Resources\ShowDashboardResource;

class GetRecommendationAction
{
    use AsAction;

    /**
     * Locally run:
     * php artisan queue:work --sleep=3 --tries=2 --backoff=30
     */

    public int $jobTries = 2; // number of times the job may be attempted
    public int $jobBackoff = 30; // number of seconds to wait before retrying 

    function handle(Recommendation $recommendation)
    {
        

        // CalculateOrganizationTotalAssetsAction::run($dashboard->organization);

        return new RecommendationResource($recommendation);
    }
}
