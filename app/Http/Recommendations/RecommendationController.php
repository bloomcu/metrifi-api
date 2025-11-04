<?php

namespace DDD\Http\Recommendations;

use Illuminate\Http\Request;
use DDD\Domain\Recommendations\Resources\RecommendationResource;
use DDD\Domain\Recommendations\Requests\UpdateRecommendationRequest;
use DDD\Domain\Recommendations\Requests\StoreRecommendationRequest;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Recommendations\Actions\Assistants\ScreenshotGrabber;
use DDD\Domain\Organizations\Organization;
use DDD\App\Services\OpenAI\AssistantService;
use DDD\App\Controllers\Controller;

class RecommendationController extends Controller
{
    public function index(Organization $organization, Request $request)
    {
        $recommendations = $organization->recommendations()->latest()->get();

        return RecommendationResource::collection($recommendations);
    }

    public function store(Organization $organization, StoreRecommendationRequest $request)
    {
        $data = [
            'organization_id' => $organization->id,
            'user_id' => auth()->id(),
            'status' => $request->status,
            'title' => $request->metadata['focus']['name'] ?? $request->title,
            'step_index' => $request->step_index,
            'prompt' => $request->prompt,
            'metadata' => $request->metadata,
        ];

        if ($request->dashboard_id) {
            $data['dashboard_id'] = $request->dashboard_id;
        }

        $recommendation = Recommendation::create($data);

        return new RecommendationResource($recommendation);
    }

    public function show(Organization $organization, Recommendation $recommendation)
    {
        return new RecommendationResource($recommendation);
    }

    public function update(Organization $organization, Recommendation $recommendation, UpdateRecommendationRequest $request)
    {
        $recommendation->update($request->validated());

        return new RecommendationResource($recommendation);
    }
    
    public function destroy(Organization $organization, Recommendation $recommendation)
    {
        $recommendation->delete();
        
        return new RecommendationResource($recommendation);
    }
}