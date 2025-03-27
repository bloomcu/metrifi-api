<?php

namespace DDD\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use DDD\Domain\Connections\Connection;
use Illuminate\Support\Facades\Crypt;
use Exception;

class EncryptConnectionTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connections:encrypt-tokens {--force : Force re-encryption of all tokens}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt the token column for existing Connection records using Laravel encrypted:array cast';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to migrate Connection tokens to encrypted:array format...');
        
        // Get all connections directly from the database
        $connections = DB::table('connections')
            ->whereNotNull('token')
            ->get();
        
        $count = $connections->count();
        $this->info("Found {$count} connections with tokens to process");
        
        if ($count === 0) {
            $this->info("No connections need encryption. Exiting.");
            return Command::SUCCESS;
        }
        
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        $success = 0;
        $failed = 0;
        
        foreach ($connections as $connection) {
            try {
                // Get the token data
                $tokenData = $connection->token;
                
                if (empty($tokenData)) {
                    $this->warn("Connection ID {$connection->id}: Empty token, skipping");
                    $bar->advance();
                    continue;
                }
                
                // Try to decode the token if it's a JSON string
                if (is_string($tokenData)) {
                    try {
                        // Check if it's already encrypted
                        if (str_starts_with($tokenData, 'eyJpdiI6')) {
                            $this->info("Connection ID {$connection->id}: Already encrypted, skipping");
                            $success++;
                            $bar->advance();
                            continue;
                        }
                        
                        // Try to decode as JSON
                        $decodedToken = json_decode($tokenData, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $tokenData = $decodedToken;
                        }
                    } catch (Exception $e) {
                        // If decoding fails, continue with the original string
                        $this->warn("Connection ID {$connection->id}: JSON decode failed, using raw string");
                    }
                }
                
                // Update the token directly in the database
                // We're bypassing the model to avoid any issues with the current state of the casts
                $updated = DB::table('connections')
                    ->where('id', $connection->id)
                    ->update([
                        'token' => Crypt::encrypt(is_array($tokenData) ? json_encode($tokenData) : $tokenData),
                        'updated_at' => now()
                    ]);
                
                if ($updated) {
                    $this->info("Connection ID {$connection->id}: Successfully encrypted");
                    $success++;
                } else {
                    $this->warn("Connection ID {$connection->id}: No changes made");
                }
            } catch (Exception $e) {
                $this->error("Failed to encrypt token for connection ID {$connection->id}: " . $e->getMessage());
                $failed++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Migration complete: {$success} successful, {$failed} failed");
        
        return Command::SUCCESS;
    }
}
