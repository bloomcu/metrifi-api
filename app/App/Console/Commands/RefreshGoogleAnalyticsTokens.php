<?php

namespace DDD\App\Console\Commands;

use DDD\App\Facades\Google\GoogleAuth;
use DDD\Domain\Connections\Connection;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshGoogleAnalyticsTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connections:refresh-google-analytics {--dry-run : List the connections that would be refreshed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh access tokens for all Google Analytics connections using the stored refresh tokens.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = Connection::where('service', 'Google Analytics - Property');

        $total = $query->count();

        if ($total === 0) {
            $this->info('No Google Analytics connections found.');

            return Command::SUCCESS;
        }

        Log::info('Refreshing Google Analytics tokens command started', ['total_connections' => $total]);

        $this->info("Found {$total} Google Analytics connection" . ($total === 1 ? '' : 's') . '.');

        if ($this->option('dry-run')) {
            $this->line('Dry run enabled; no tokens will be refreshed.');

            $query->orderBy('id')->each(function (Connection $connection): void {
                Log::debug('Dry run - would refresh Google Analytics connection', [
                    'connection_id' => $connection->id,
                    'name' => $connection->name,
                ]);
                $this->line("- [ID {$connection->id}] {$connection->name}");
            });

            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $refreshed = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        $query->orderBy('id')->each(function (Connection $connection) use (&$bar, &$refreshed, &$skipped, &$failed, &$errors): void {
            $originalToken = $connection->token ?? [];
            $tokenUpdated = false;

            try {
                Log::debug('Validating Google Analytics connection within refresh command', [
                    'connection_id' => $connection->id,
                ]);
                GoogleAuth::validateConnection($connection);

                if ($connection->token != $originalToken) {
                    $tokenUpdated = true;
                }

                $updatedToken = $connection->token ?? [];
                $needsRefreshToken = !empty($originalToken['refresh_token']) && empty(Arr::get($updatedToken, 'refresh_token'));

                if ($needsRefreshToken) {
                    $updatedToken['refresh_token'] = $originalToken['refresh_token'];
                    $connection->token = $updatedToken;
                    $connection->save();
                    $tokenUpdated = true;
                    Log::warning('Refresh command restored missing refresh token for connection', [
                        'connection_id' => $connection->id,
                    ]);
                }

                if ($tokenUpdated) {
                    $refreshed++;
                    Log::info('Refresh command updated Google Analytics token', [
                        'connection_id' => $connection->id,
                    ]);
                } else {
                    $skipped++;
                }
            } catch (Throwable $exception) {
                $failed++;
                $errors[] = [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'message' => $exception->getMessage(),
                ];
                Log::error('Refresh command failed to update Google Analytics connection', [
                    'connection_id' => $connection->id,
                    'name' => $connection->name,
                    'message' => $exception->getMessage(),
                ]);
            }

            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Refreshed: {$refreshed}");
        $this->info("Skipped (already valid): {$skipped}");

        if ($failed > 0) {
            $this->error("Failed: {$failed}");

            foreach ($errors as $error) {
                $this->error("- [ID {$error['id']}] {$error['name']}: {$error['message']}");
            }
        }

        Log::info('Refreshing Google Analytics tokens command completed', [
            'refreshed' => $refreshed,
            'skipped' => $skipped,
            'failed' => $failed,
        ]);

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
