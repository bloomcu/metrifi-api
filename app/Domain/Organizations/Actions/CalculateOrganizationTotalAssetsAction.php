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
        // Initialize assets object
        $assets = [
            'median' => [
                'assets' => 0,
                'potential' => 0,
                'total_potential' => 0,
            ],
            'max' => [
                'assets' => 0,
                'potential' => 0,
                'total_potential' => 0,
            ],
        ];

        // Tally up the assets and potential assets for each dashboard
        foreach ($organization->dashboards as $dashboard) {
            if (!$dashboard->analyses->count()) {
                continue;
            }

            $assets['median']['assets'] += $dashboard->medianAnalysis?->subject_funnel_assets / 100 ?? 0;
            $assets['median']['potential'] += $dashboard->medianAnalysis?->subject_funnel_potential_assets / 100 ?? 0;

            $assets['max']['assets'] += $dashboard->maxAnalysis?->subject_funnel_assets / 100 ?? 0;
            $assets['max']['potential'] += $dashboard->maxAnalysis?->subject_funnel_potential_assets / 100 ?? 0;
        }

        // Add up total potentials
        $assets['median']['total_potential'] = $assets['median']['assets'] + $assets['median']['potential'];
        $assets['max']['total_potential'] = $assets['max']['assets'] + $assets['max']['potential'];

        // Update the organization's assets
        $organization->update([
            'assets' => $assets
        ]);

        return new OrganizationResource($organization);
    }
}
