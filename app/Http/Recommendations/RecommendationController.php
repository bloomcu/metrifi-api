<?php

namespace DDD\Http\Recommendations;

use Illuminate\Support\Facades\Bus;
use Illuminate\Http\Request;
use DDD\Domain\Recommendations\Resources\RecommendationResource;
use DDD\Domain\Recommendations\Requests\UpdateRecommendationRequest;
use DDD\Domain\Recommendations\Requests\StoreRecommendationRequest;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Recommendations\Actions\Assistants\UIAnalyzer;
use DDD\Domain\Recommendations\Actions\Assistants\ScreenshotGrabber;
use DDD\Domain\Recommendations\Actions\Assistants\PageBuilderOpenAI;
use DDD\Domain\Recommendations\Actions\Assistants\ContentWriter;
use DDD\Domain\Recommendations\Actions\Assistants\ComponentPicker;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Dashboards\Dashboard;
use DDD\App\Services\Screenshot\ThumbioService;
use DDD\App\Services\Screenshot\ScreenshotInterface;
use DDD\App\Services\OpenAI\AssistantService;
use DDD\App\Controllers\Controller;

class RecommendationController extends Controller
{
    public function index(Organization $organization, Dashboard $dashboard)
    {
        $recommendations = $dashboard->recommendations()->latest()->get();

        return RecommendationResource::collection($recommendations);
    }

    public function store(
        Organization $organization, 
        Dashboard $dashboard, 
        StoreRecommendationRequest $request, 
        AssistantService $assistant,
    ){
        $thread = $assistant->createThread();

        $recommendation = $dashboard->recommendations()->create([
            'organization_id' => $organization->id,
            'title' => $request->metadata['focus']['name'],
            'thread_id' => $thread['id'],
            'step_index' => $request->step_index,
            'prompt' => $request->prompt,
            'secret_shopper_prompt' => $request->secret_shopper_prompt,
            'metadata' => $request->metadata,
        ]);

        ScreenshotGrabber::dispatch($recommendation);

        return new RecommendationResource($recommendation);
    }

    public function show(Organization $organization, Dashboard $dashboard, AssistantService $assistant, Recommendation $recommendation)
    {
        return new RecommendationResource($recommendation);
    }

    public function update(Organization $organization, Dashboard $dashboard, Recommendation $recommendation, UpdateRecommendationRequest $request)
    {
        $recommendation->update($request->validated());

        return new RecommendationResource($recommendation);
    }

    // public function destroy(Organization $organization, Dashboard $dashboard, Analysis $analysis)
    // {
    //     $analysis->delete();

    //     return new AnalysisResource($analysis);
    // }
}