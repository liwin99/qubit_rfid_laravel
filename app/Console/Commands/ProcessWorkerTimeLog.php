<?php

namespace App\Console\Commands;

use App\Models\MasterProject;
use App\Models\StagingWorkerTimeLog;
use App\Models\WorkerTimeLog;
use App\Services\WorkerTimeLogService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessWorkerTimeLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:process-worker-time-log {date?}
    { --staging : staging and final worker time log }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process worker time log for single-reader and double-reader.';

    protected WorkerTimeLogService $workerTimeLogService;

    public function __construct(WorkerTimeLogService $workerTimeLogService)
    {
        parent::__construct();
        $this->workerTimeLogService = $workerTimeLogService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date');
        $staging = $this->option('staging');

        $startingDate = null;

        $eligibleProjects = [];

        if ($date) {
            $startingDate = Carbon::parse($date)->toDateString();

            // if user insert specific date to re run worker time log, then delete existing tag-read record
            $this->info("Deleting existing data for period {$startingDate}");
            $projects = MasterProject::get();
        } else {
            // Get all projects where daily_period_to is within previous hour
            $projects = MasterProject::whereBetween('daily_period_to', [now()->subHour()->subMinute()->startOfHour(), now()->subHour()->subMinute()->endOfHour()])->get();
        }

        $this->info("Get period by project");

        foreach ($projects as $project) {
            $eligibleProjects[] = $this->workerTimeLogService->getPeriodByProject($project, $startingDate);
        }

        $this->info('Clearing staging and worker table');

        foreach ($eligibleProjects as $project) {
            StagingWorkerTimeLog::where('project_id', $project['project']->id)->where('period', $project['period'])->delete();
            WorkerTimeLog::where('project_id', $project['project']->id)->where('period', $project['period'])->delete();
        }

        $this->info("Processing single reader");

        foreach ($eligibleProjects as $project) {
            $this->workerTimeLogService->processSingleReader($project['project'], $project['startingPeriod'], $project['endingPeriod'], $project['period']);
        }

        $this->info("Processing double reader");

        foreach ($eligibleProjects as $project) {
             $this->workerTimeLogService->processDoubleReader($project['project'], $project['startingPeriod'], $project['endingPeriod'], $project['period']);
        }

        $this->info("Processing staging worker time log");

        foreach ($eligibleProjects as $project) {
            $this->workerTimeLogService->processStagingWorkerTimeLog($project['project'], $project['period']);
        }

        if (!$staging) {
            $this->info("Processing final worker time log and sending data to TMS");
            foreach ($eligibleProjects as $project) {
                $this->workerTimeLogService->sendSummarizedTimeLogToTms($project['project'], $project['period']);
            }
        }
    }
}
