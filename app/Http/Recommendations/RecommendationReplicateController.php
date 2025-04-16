<?php

namespace DDD\Http\Recommendations;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use DDD\Domain\Recommendations\Resources\RecommendationResource;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Organizations\Organization;
use DDD\App\Controllers\Controller;

class RecommendationReplicateController extends Controller
{
    public function store(Organization $organization, Recommendation $recommendation)
    {
        // Start a database transaction to ensure all operations succeed or fail together
        return DB::transaction(function () use ($organization, $recommendation) {
            // 1. Duplicate the recommendation
            $newRecommendation = $recommendation->replicate();
            $newRecommendation->title = $recommendation->title . ' (Copy)';
            // $newRecommendation->organization_id = $organization->id;
            $newRecommendation->user_id = auth()->id();
            $newRecommendation->save();

            // 2. Duplicate associated files
            // foreach ($recommendation->files as $file) {
            //     // Copy the file in storage
            //     $originalPath = $file->filename;
            //     $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
            //     $newPath = 'files/' . $organization->id . '/' . uniqid() . '.' . $extension;
                
            //     if (Storage::disk($file->disk)->exists($originalPath)) {
            //         Storage::disk($file->disk)->copy($originalPath, $newPath);
                    
            //         // Create new file record
            //         $newFile = $file->replicate();
            //         $newFile->filename = $newPath;
            //         $newFile->organization_id = $organization->id;
            //         $newFile->user_id = auth()->id();
            //         $newFile->save();
                    
            //         // Associate with the new recommendation with the same type
            //         $pivotType = $file->pivot->type ?? null;
            //         $newRecommendation->files()->attach($newFile->id, ['type' => $pivotType]);
            //     }
            // }

            // 3. Duplicate pages and their blocks
            foreach ($recommendation->pages as $page) {
                $newPage = $page->replicate();
                $newPage->recommendation_id = $newRecommendation->id;
                // $newPage->organization_id = $organization->id;
                $newPage->user_id = auth()->id();
                $newPage->save();
                
                // Duplicate blocks for this page
                foreach ($page->blocks as $block) {
                    $newBlock = $block->replicate();
                    $newBlock->page_id = $newPage->id;
                    // $newBlock->organization_id = $organization->id;
                    $newBlock->user_id = auth()->id();
                    $newBlock->save();
                }
            }
            
            return new RecommendationResource($newRecommendation);
        });
    }
}
