<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use DDD\App\Services\Grok\GrokService;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use DDD\Domain\Recommendations\Recommendation;
use DDD\App\Services\OpenAI\AssistantService;

class PageBuilderGrok implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;
    
    public $name = 'page_builder';
    public $timeout = 60;
    public $tries = 50;
    public $backoff = 5;

    protected AssistantService $assistant;
    protected GrokService $grok;

    public function __construct(AssistantService $assistant, GrokService $grok)
    {
        $this->assistant = $assistant;
        $this->grok = $grok;
    }

    function handle(Recommendation $recommendation)
    {
      $recommendation->update(['status' => $this->name . '_in_progress']);

      // Get messages from the thread
      $messages = $this->assistant->getMessagesAsString($recommendation->thread_id);

      $chat = $this->grok->chat(
        instructions: 'You are Grok, an expert website section developer who uses html and tailwind css. You build beautiful sections of a webpage one at a time using fontawesome icons and placeholder images. You return the section code as a string, nothing else before or after.',
        message: 'Build section ' . $recommendation->sections_built + 1 . ' in the Content Outline: ' . $messages,
      );

      // Update the recommendation with the new section
      $built = $recommendation->sections_built + 1;
      $html = preg_match('/```html(.*?)```/s', $chat, $matches) ? $matches[1] : '';
      $recommendation->update([
          'sections_built' => $built,
          'prototype' => $recommendation->prototype . $html,
      ]);
  
      // If there are more sections to build, dispatch a new instance of the job with a delay
      if ($built < $recommendation->sections_count) {
        self::dispatch($recommendation)->delay(now()->addSeconds(3));
        return;
      }
      
      // Done, no more sections to build
      $recommendation->update([
          'status' => 'done',
      ]);

      return;
    }
}
