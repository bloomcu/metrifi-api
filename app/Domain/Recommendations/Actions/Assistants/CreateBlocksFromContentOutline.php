<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Recommendations\Actions\Assistants\BlockBuilderMagicPatterns;
use DDD\App\Neuron\Agents\Recommendations\ContentOutlineToSectionsAgent;
use NeuronAI\Chat\Messages\UserMessage;

class CreateBlocksFromContentOutline implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;

    public $name = 'content_json_formatter';
    public $jobTimeout = 60;
    public $jobTries = 50;
    public $jobBackoff = 5;

    function handle(Recommendation $recommendation)
    {
        $recommendation->update(['status' => $this->name . '_in_progress']);

        $result = ContentOutlineToSectionsAgent::make()->structured(
            new UserMessage($recommendation->content_outline ?? ''),
            \DDD\App\Neuron\Output\ContentOutlineSections::class,
        );

        $sections = $result->sections ?? [];

        $page = $recommendation->pages()->create([
            'organization_id' => $recommendation->organization_id,
            'user_id' => $recommendation->user_id,
            'title' => $recommendation->title,
        ]);

        foreach ($sections as $index => $section) {
            $page->blocks()->create([
                'organization_id' => $recommendation->organization_id,
                'user_id' => $recommendation->user_id,
                'order' => (int) $index + 1,
                'status' => 'generating',
                'outline' => $section->outline ?? '',
            ]);
        }

        $blocks = $page->blocks()->get();
        foreach ($blocks as $block) {
            BlockBuilderMagicPatterns::dispatch($recommendation, $block)->delay(2);
        }

        $recommendation->update(['status' => $this->name . '_completed']);

        return;
    }
}
