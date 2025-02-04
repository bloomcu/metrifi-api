<?php

namespace DDD\Domain\Analyses\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Analyses\Analysis;

class Step3CalculatePotentialAssets
{
    use AsAction;

    function handle(Analysis $analysis, $subjectFunnel, $comparisonFunnels)
    {
        $assets = $subjectFunnel['report']['assets'] * 100; // In cents

        $potential = $analysis->bofi_asset_change * 100; // In cents
        if ($potential < 0) { // If the change is negative, we don't want a negative potential
            $potential = 0;
        }

        // Get profit per user by first getting assets per user
        $assetsPerUser = ($assets / 100) / $analysis->subject_funnel_users;
        $returnOnAssets = $analysis->dashboard->organization->return_on_assets;
        $profitPerUser = $assetsPerUser * ($returnOnAssets / 100);

        // $profitPerUser = $analysis->dashboard->organization->return_on_assets;

        // Build reference
        $reference = $analysis->reference .= $this->generateReference([
            'assets' => $assets,
            'potential' => $potential,
        ]);

        // Update analysis
        $analysis->update([
            'subject_funnel_assets' => $assets,
            'subject_funnel_potential_assets' => $potential,
            'subject_funnel_profit_per_user' => $profitPerUser,
            'reference' => $reference,
        ]);

        return $analysis;
    }

    function generateReference($reference) {
        $html = '';

        $html .= "<p><strong>Subject funnel assets:</strong> {$reference['assets']}</p>";
        $html .= "<p><strong>Subject funnel potential:</strong> {$reference['potential']}</p>";

        return $html;
    }
}
