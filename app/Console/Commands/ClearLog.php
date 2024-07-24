<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ClearLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:clear-log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear logs that exceed 3 months old';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threeMonthsAgo = Carbon::now()->subMonths(3);

        DB::table('lambda_logs')
            ->where('created_at', '<', $threeMonthsAgo)
            ->delete();

        DB::table('inbound_logs')
            ->where('created_at', '<', $threeMonthsAgo)
            ->delete();

        $this->info('Old logs deleted successfully.');
    }
}
