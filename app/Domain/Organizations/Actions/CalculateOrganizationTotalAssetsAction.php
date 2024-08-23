<?php

namespace DDD\Domain\Organizations\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Organizations\Resources\OrganizationResource;
use DDD\Domain\Organizations\Organization;

class CalculateOrganizationTotalAssetsAction
{
    use AsAction;

    function handle(Organization $organization)
    {
        $assets = [
            'median' => [
                'assets' => 0,
                'potential' => 0
            ],
            'max' => [
                'assets' => 0,
                'potential' => 0
            ],
        ];

        foreach ($organization->dashboards as $dashboard) {
            if (!$dashboard->analyses->count()) {
                continue;
            }

            $assets['median']['assets'] += $dashboard->medianAnalysis->subject_funnel_assets;
            $assets['median']['potential'] += $dashboard->medianAnalysis->subject_funnel_potential_assets;

            $assets['max']['assets'] += $dashboard->maxAnalysis->subject_funnel_assets;
            $assets['max']['potential'] += $dashboard->maxAnalysis->subject_funnel_potential_assets;
        }

        $organization->update([
            'assets' => $assets
        ]);

        return new OrganizationResource($organization);
    }
}
