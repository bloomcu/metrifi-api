<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Recommendations\Actions\Assistants\BlockBuilderMagicPatterns;
use DDD\App\Services\OpenAI\GPTService;

class CreateBlocksFromContentOutline implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;
    
    public $name = 'content_json_formatter';
    public $timeout = 60;
    public $tries = 50;
    public $backoff = 5;

    protected GPTService $gpt;

    public function __construct(GPTService $gpt)
    {
        $this->gpt = $gpt;
    }

    function handle(Recommendation $recommendation)
    {
      $recommendation->update(['status' => $this->name . '_in_progress']);

      $response = $this->gpt->chat(
        model: 'gpt-4o',
        instructions: 
          'I am going to give you a Content Outline. Your job is to convert it into a JSON array.
           The Content Outline contains Sections, labeled Section 1, Section 2, Section 3, etc. 
           Your JSON array must contain an object for each Section.',
        message: $recommendation->content_outline,
        responseFormat: '{sections: [{ outline: string }]}'
      );
      
      // $response will be a json string, we need to decode it into actual json
      $json = json_decode($response, true);
      Log::info('GPT Response: ' . $response);

      // Create a new page
      $page = $recommendation->pages()->create([
        'organization_id' => $recommendation->organization_id,
        'user_id' => $recommendation->user_id,
        'title' => $recommendation->title,
      ]);

      // For each section in content outline, create a new block
      foreach ($json['sections'] as $index => $section) {
        $page->blocks()->create([
            'organization_id' => $recommendation->organization_id,
            'user_id' => $recommendation->user_id,
            'order' => (int)$index + 1,
            'status' => 'generating',
            'outline' => $section['outline'],
        ]);
      }

      // Queue block builder
      $blocks = $page->blocks()->get();
      foreach ($blocks as $index => $block) {
        BlockBuilderMagicPatterns::dispatch($recommendation, $block)->delay(2);
      }
      
      // We're done
      $recommendation->update([
        'status' => $this->name . '_completed',
      ]);

      return;
    }
}
