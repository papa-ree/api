<?php

namespace Bale\Api\Commands;

use Bale\Api\Models\ApiToken;
use Illuminate\Console\Command;

class PruneExpiredTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:prune-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete API tokens revoked or expired longer than the prune window';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pruneDays = (int) config('api.token.prune_days', 30);
        $cutoff = now()->subDays($pruneDays);

        $deleted = ApiToken::query()
            ->where(function ($query) use ($cutoff) {
                $query->where('revoked_at', '<', $cutoff)
                    ->orWhere('expires_at', '<', $cutoff);
            })
            ->delete();

        $this->info("Deleted {$deleted} API token(s) revoked or expired before {$cutoff->toDateTimeString()}.");

        return self::SUCCESS;
    }
}
