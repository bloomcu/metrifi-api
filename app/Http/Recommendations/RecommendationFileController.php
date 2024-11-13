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
        $fileIds = $request->file_ids;
        $pivotData = ['type' => $request->type];

        foreach ($fileIds as $fileId) {
            $recommendation->files()->attach($fileId, $pivotData);
        }

        return response()->json(['message' => 'Files attached to recommendation']);
    }
}