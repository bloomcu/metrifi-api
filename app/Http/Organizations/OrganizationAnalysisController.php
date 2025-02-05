<?php

namespace DDD\Http\Organizations;

use DDD\Domain\Organizations\Organization;
use DDD\Domain\Analyses\Actions\AnalyzeDashboardAction;
use DDD\App\Controllers\Controller;

class OrganizationAnalysisController extends Controller
{
  public function analyzeOrganizationDashboards(Organization $organization)
  {   
      $dashboards = $organization->dashboards()
        ->with(['organization', 'medianAnalysis', 'maxAnalysis'])
        ->get();

      foreach ($dashboards as $dashboard) {
          $dashboard->update([
              'analysis_in_progress' => 1,
          ]);

          AnalyzeDashboardAction::dispatch($dashboard);
      }
  }
}
