<?php

namespace DDD\Http\Organizations;

use DDD\Domain\Organizations\Organization;
use DDD\Domain\Organizations\Actions\SendWeeklyAnalysisEmailAction;
use DDD\App\Controllers\Controller;

class OrganizationWeeklyAnalysisEmailController extends Controller
{

    /**
     * Send the weekly email
     */
    public function send(Organization $organization)
    {
        SendWeeklyAnalysisEmailAction::run($organization);

        return response()->json(['message' => 'Weekly analysis emails queued successfully']);
    }
}
