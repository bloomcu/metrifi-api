<?php

namespace DDD\Http\Recommendations;

use DDD\Domain\Recommendations\Resources\RecommendationResource;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Recommendations\Actions\Assistants\ScreenshotGrabber;
use DDD\Domain\Organizations\Organization;
use DDD\App\Controllers\Controller;

class RecommendationGenerateController extends Controller
{
    public function update(Organization $organization, Recommendation $recommendation)
    {
        $recommendation->update([
            'status' => 'queued',
        ]);

        ScreenshotGrabber::dispatch($recommendation);

        return new RecommendationResource($recommendation);
    }
}