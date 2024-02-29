<?php

namespace DDD\Domain\Funnels\Jobs;

use Throwable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Actions\GenerateFunnelAction;
use DDD\Domain\Connections\Connection;

class GenerateFunnelJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int|array $backoff = [3, 6];

    public function __construct(
        public Organization $organization, 
        public Connection $funnelConnection, 
        public string $terminalPagePath,
        public int $userId,
    ) {}

    public function handle()
    {
        if ($this->batch()->cancelled()) return;

        GenerateFunnelAction::run($this->organization, $this->funnelConnection, $this->terminalPagePath, $this->userId);
    }
}
