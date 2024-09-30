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

        // $potential = $assets + $change;

        // Build reference
        $reference = $analysis->reference .= $this->generateReference([
            'assets' => $assets,
            // 'change' => $change,
            'potential' => $potential,
        ]);

        // Update analysis
        $analysis->update([
            'subject_funnel_assets' => $assets,
            'subject_funnel_potential_assets' => $potential,
            'reference' => $reference,
        ]);

        return $analysis;
    }

    function generateReference($reference) {
        $html = '';

        $html .= "<p><strong>Subject funnel assets:</strong> {$reference['assets']}</p>";
        // $html .= "<p><strong>Biggest opportunity potential assets:</strong> {$reference['change']}</p>";
        $html .= "<p><strong>Subject funnel potential:</strong> {$reference['potential']}</p>";

        return $html;
    }
}
