<?php

namespace DDD\Http\Recommendations;

use DDD\Http\Files\Resources\FileResource;
use DDD\Domain\Recommendations\Resources\RecommendationResource;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Files\File;
use DDD\App\Controllers\Controller;
use Illuminate\Http\Request;

class RecommendationFileController extends Controller
{
    public function attach(Organization $organization, Recommendation $recommendation, Request $request)
    {   
        $recommendation->files()->sync($request->file_ids);

        return response()->json(['message' => 'Files attached to recommendation']);
        // return new FileResource($file);
    }
}