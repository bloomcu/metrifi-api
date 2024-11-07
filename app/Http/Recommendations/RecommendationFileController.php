<?php

namespace DDD\Http\Recommendations;

use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Organizations\Organization;
use DDD\App\Controllers\Controller;
use Illuminate\Http\Request;

class RecommendationFileController extends Controller
{
    public function attach(Organization $organization, Recommendation $recommendation, Request $request)
    {   
        $recommendation->files()->syncWithPivotValues(
            $request->file_ids, 
            ['type' => $request->type]
        );

        return response()->json(['message' => 'Files attached to recommendation']);
    }
}