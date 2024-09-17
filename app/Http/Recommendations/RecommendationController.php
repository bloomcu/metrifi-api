<?php

namespace DDD\Http\Recommendations;

use Illuminate\Http\Request;
use DDD\Domain\Recommendations\Resources\RecommendationResource;
use DDD\Domain\Recommendations\Requests\StoreRecommendationRequest;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Dashboards\Dashboard;
use DDD\App\Services\OpenAI\AssistantService;
use DDD\App\Controllers\Controller;

class RecommendationController extends Controller
{
    // public function index(Organization $organization, Dashboard $dashboard)
    // {
    //     return AnalysisResource::collection($dashboard->analyses);
    // }

    public function store(Organization $organization, Dashboard $dashboard, StoreRecommendationRequest $request, AssistantService $assistant)
    {   
        $response = $assistant->getAssistantResponse(
            assistantId: 'asst_CMWB6kdTk4KH9zJ3W6U4x8er', 
            message: 'Hello assistant, is this working?'
        );

        return $response;

        // $recommendation = $dashboard->recommendations()->create($request->validated());

        // return new RecommendationResource($recommendation);
    }

    public function show(Organization $organization, Dashboard $dashboard, Recommendation $recommendation, AssistantService $assistant)
    {
        return $assistant->getMessagesList(
            threadId: 'thread_MYoqWrNUgPTUn5RiELMKRisu'
        );

        return new RecommendationResource($recommendation);
    }

    // public function update(Organization $organization, Dashboard $dashboard, Analysis $analysis, AnalysisUpdateRequest $request)
    // {
    //     $analysis->update($request->validated());

    //     return new AnalysisResource($analysis);
    // }

    // public function destroy(Organization $organization, Dashboard $dashboard, Analysis $analysis)
    // {
    //     $analysis->delete();

    //     return new AnalysisResource($analysis);
    // }
}
