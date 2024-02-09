<?php

namespace DDD\Domain\Funnels\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Bus\Queueable;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\Actions\GetValidPagePaths;
use DDD\Domain\Funnels\Actions\GetEndpointSegments;

class GenerateFunnelStepsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $funnel;
    public $terminalPagePath;

    public function __construct(Funnel $funnel, string $terminalPagePath)
    {
        $this->funnel = $funnel;
        $this->terminalPagePath = $terminalPagePath;
    }

    public function handle()
    {
        // Mark funnel as automating
        $this->funnel->update([
            'automating' => true,
        ]);

        // Break funnel endpoint into parts.
        $segments = GetEndpointSegments::run($this->terminalPagePath);

        // Validate the parts.
        $validated = GetValidPagePaths::run($this->funnel, $segments->data->pagePaths);

        // Create funnel steps.
        foreach ($validated->data->pagePaths as $key => $pagePath) {
            $this->funnel->steps()->create([
                'order' => $key + 1,
                'name' => $pagePath,
                'measurables' => [
                    [
                        'metric' => 'pageViews',
                        'measurable' => $pagePath,
                    ]
                ]
            ]);
        }

        // Mark funnel automation as complete
        $this->funnel->update([
            'automating' => false,
        ]);
    }
}
