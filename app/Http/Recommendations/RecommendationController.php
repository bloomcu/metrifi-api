<?php

namespace DDD\Http\Recommendations;

use Illuminate\Support\Facades\Bus;
use Illuminate\Http\Request;
use DDD\Domain\Recommendations\Resources\RecommendationResource;
use DDD\Domain\Recommendations\Requests\StoreRecommendationRequest;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Recommendations\Actions\Assistants\UIAnalyzer;
use DDD\Domain\Recommendations\Actions\Assistants\PageBuilder;
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
    // public function index(Organization $organization, Dashboard $dashboard)
    // {
    //     return AnalysisResource::collection($dashboard->analyses);
    // }

    public function store(
        Organization $organization, 
        Dashboard $dashboard, 
        StoreRecommendationRequest $request, 
        AssistantService $assistant
    ){   
        $thread = $assistant->createThread();

        $recommendation = $dashboard->recommendations()->create([
            ...$request->validated(),
            'thread_id' => $thread['id']
        ]);

        UIAnalyzer::dispatch($recommendation);

        // Bus::chain([
        //     UIAnalyzer::makeJob($recommendation),
        //     ContentWriter::makeJob($recommendation),
        //     ComponentPicker::makeJob($recommendation),
        //     PageBuilder::makeJob($recommendation),
        // ])->dispatch();

        return new RecommendationResource($recommendation);
    }

    public function show(Organization $organization, Dashboard $dashboard, AssistantService $assistant, Recommendation $recommendation)
    {
        // PageBuilder::dispatch($recommendation);

        // $message = $assistant->getFinalMessage(threadId: $recommendation->thread_id);
        // return $message;
        // $result = preg_match('/<body[^>]*>(.*?)<\/body>/is', $message, $matches);
        // return $result;


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