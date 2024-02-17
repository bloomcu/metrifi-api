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
use DDD\Domain\Funnels\Actions\StoreFunnelAction;
use DDD\Domain\Connections\Connection;

class StoreFunnelJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Organization $organization, 
        public Connection $funnelConnection, 
        public string $terminalPagePath,
        public int $userId,
    ) {}

    public function handle()
    {
        StoreFunnelAction::run($this->organization, $this->funnelConnection, $this->terminalPagePath, $this->userId);
    }
}
