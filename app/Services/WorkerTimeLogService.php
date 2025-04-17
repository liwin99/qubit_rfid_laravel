<?php

namespace App\Services;

use App\Models\RfidReaderPairing;
use App\Models\StagingWorkerTimeLog;
use App\Models\WorkerTimeLog;
use App\Repositories\RfidTagReadRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkerTimeLogService
{
    private RfidTagReadRepository $rfidTagReadRepository;

    public function __construct(RfidTagReadRepository $rfidTagReadRepository)
    {
        $this->rfidTagReadRepository = $rfidTagReadRepository;
    }

    public function processSingleReader($project, $startingPeriod, $endingPeriod, $period)
    {
        // getting all tagRead joining readers,projects,pairings order DESC by tag_read_datetime
        $tagReads = $this->rfidTagReadRepository->getSingleReaderTagReads($startingPeriod, $endingPeriod, $project);

        $epcGroup = $tagReads->groupBy('epc');

        foreach ($epcGroup as $epcTagreads) {
            $tempStagingWorkerTimeLogs = [];

            // loop thru all tag_reads by this epc and transform data to correct format and save in tempStagingWorkerTimeLogs array
            $epcTagreads->each(function ($tagRead) use ($project, $period, &$tempStagingWorkerTimeLogs) {
                $tempStagingWorkerTimeLogs[] = $this->transformsToStagingWorkerForSingleReader($tagRead, $project, $period);
            });

            StagingWorkerTimeLog::insert($tempStagingWorkerTimeLogs);
        }
    }

    public function getPeriodByProject($project, $chosenDate)
    {
        // determine if crossDay or sameDay for project
        $isSameDay = Carbon::parse($project->daily_period_from)->timezone('GMT+8')
            ->lessThan(Carbon::parse($project->daily_period_to)->timezone('GMT+8'));

        if ($chosenDate === null) {
            // this is default period used
            if ($isSameDay) {
                $startingDate = now()->subHour()->toDateString();

                $startingPeriod = Carbon::parse("$startingDate $project->daily_period_from");
                $endingPeriod = Carbon::parse("$startingDate $project->daily_period_to");
            } else {
                $startingDate = now()->subHour()->subDay()->toDateString();
                $endingDate = now()->subHour()->toDateString();

                $startingPeriod = Carbon::parse("$startingDate $project->daily_period_from");
                $endingPeriod = Carbon::parse("$endingDate $project->daily_period_to");
            }

            $startingPeriodMyt = clone $startingPeriod;
            $startingDateMyt = $startingPeriodMyt->timezone('GMT+8');
            $startingDate = $startingDateMyt->toDateString();
        } else {
            // this is chosen period
            if ($isSameDay) {
                $startingDate = Carbon::parse($chosenDate)->toDateString();

                $startingPeriod = Carbon::parse("$startingDate $project->daily_period_from");
                $endingPeriod = Carbon::parse("$startingDate $project->daily_period_to");
            } else {
                $startingDate = Carbon::parse($chosenDate)->toDateString();
                $endingDate = Carbon::parse($startingDate)->addDay()->toDateString();

                $startingPeriod = Carbon::parse("$startingDate $project->daily_period_from");
                $endingPeriod = Carbon::parse("$endingDate $project->daily_period_to");
            }

            // this is chosen period
            $startingPeriodMyt = clone $startingPeriod;
            $endingPeriodMyt = clone $endingPeriod;
            $startingPeriodMyt->timezone('GMT+8');
            $endingPeriodMyt->timezone('GMT+8');

            // logic to determine deduct day or not when it's before 8am
            // first convert to MYT, then check if day is +1, if yes, deduct day and then convert to UTC again
            if ($startingPeriodMyt->day > $startingPeriod->day) {
                $startingPeriodMyt->subDay();
                $startingPeriod = $startingPeriodMyt->timezone('UTC');
                $endingPeriodMyt->subDay();
                $endingPeriod = $endingPeriodMyt->timezone('UTC');
            } else {
                $startingPeriod = $startingPeriodMyt->timezone('UTC');
                $endingPeriod = $endingPeriodMyt->timezone('UTC');
            }
        }

        return [
            'startingPeriod' => $startingPeriod->toDateTimeString(),
            'endingPeriod' => $endingPeriod->toDateTimeString(),
            'period' => $startingDate,
            'project' => $project,
        ];
    }

    public function transformsToStagingWorkerForSingleReader($tagRead, $project, $period)
    {
        return [
            'reader_1_id' => $tagRead->reader_id,
            'reader_1_name' => $tagRead->reader_name,
            'reader_2_id' => null,
            'reader_2_name' => null,
            'epc' => $tagRead->epc,
            'project_id' => $project->id,
            'project_name' => $project->name,
            'tag_read_datetime' => $tagRead->tag_read_datetime,
            'direction' => null,
            'period' => $period,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function transformsToWorkerTimeLog($clockInTimeLog, $clockOutTimeLog, $lastTimeLog)
    {
        return [
            'reader_1_id' => $clockInTimeLog ? ($clockInTimeLog->reader_2_id ?? $clockInTimeLog->reader_1_id ) : null,
            'reader_1_name' => $clockInTimeLog ? ($clockInTimeLog->reader_2_name ?? $clockInTimeLog->reader_1_name) : null,
            'reader_2_id' => $clockOutTimeLog ? ($clockOutTimeLog->reader_2_id ?? $clockOutTimeLog->reader_1_id) : null,
            'reader_2_name' => $clockOutTimeLog ? ($clockOutTimeLog->reader_2_name ?? $clockOutTimeLog->reader_1_name) : null,
            'epc' => $lastTimeLog->epc,
            'project_id' => $lastTimeLog->project_id,
            'project_name' => $lastTimeLog->project_name,
            'clock_in' => $clockInTimeLog ? $clockInTimeLog->tag_read_datetime : null,
            'clock_out' => $clockOutTimeLog ? $clockOutTimeLog->tag_read_datetime : null,
            'period' => $lastTimeLog->period,
            'last_tag_read' => $lastTimeLog ? $lastTimeLog->tag_read_datetime : $lastTimeLog->tag_read_datetime,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function processDoubleReader($project, $startingPeriod, $endingPeriod, $period)
    {
        $tagReads = $this->rfidTagReadRepository->getDoubleReaderTagReads($startingPeriod, $endingPeriod, $project);

        $epcGroup = $tagReads->groupBy('epc');

        $skipTagReadId = [];
        $tempStagingWorkerTimeLogs = [];

        foreach ($epcGroup as $epcTagreads) {
            foreach ($epcTagreads as $tagRead) {
                $nextTagRead = $this->findNextTagRead($tagRead, $epcTagreads, config('qubit.double_reader_interval_seconds'));

                if (in_array($tagRead->id, $skipTagReadId) || !isset($nextTagRead) || $tagRead->reader_id == $tagRead->paired_reader_id) {
                    continue;
                } else {
                    $skipTagReadId[] = $nextTagRead->id;
                    $tempStagingWorkerTimeLog = $this->transformsToStagingWorkerForDoubleReader($tagRead, $nextTagRead, $project, $period);
                    if (isset($tempStagingWorkerTimeLog)) {
                        $tempStagingWorkerTimeLogs[] = $tempStagingWorkerTimeLog;
                    }
                }
            }
        }
        StagingWorkerTimeLog::insert($tempStagingWorkerTimeLogs);
    }

    public function findNextTagRead($tagRead, $tagReadByEpc, $withinSeconds)
    {
        $nextTagReadId = $tagRead->paired_reader_id;

        $filtered = $tagReadByEpc->where('reader_id', '=', $nextTagReadId)
            ->where(function ($item) use ($tagRead, $withinSeconds) {
                $tagReadTimestamp = Carbon::parse($tagRead->tag_read_datetime);
                $nextTagReadTimestamp = Carbon::parse($item->tag_read_datetime);
                $timeDiff = $tagReadTimestamp->diffInSeconds($nextTagReadTimestamp);

                return $timeDiff > 0 && $timeDiff <= $withinSeconds;
            })->first();

        return $filtered;
    }

    public function transformsToStagingWorkerForDoubleReader($tagRead, $nextTagRead, $project, $period)
    {
        $tagReadPosition = $tagRead->current_reader_position;
        $tagReadTimestamp = Carbon::parse($tagRead->tag_read_datetime)->getTimestampMs();
        $nextTagReadTimestamp = Carbon::parse($nextTagRead->tag_read_datetime)->getTimestampMs();

        if ($tagReadTimestamp > $nextTagReadTimestamp) {
            $direction = $tagReadPosition == RfidReaderPairing::CLOSE_TO_EXIT ? 'OUT' : 'IN';
            $tag_read_datetime = $tagReadPosition == RfidReaderPairing::CLOSE_TO_EXIT ?
                $tagRead->tag_read_datetime : $nextTagRead->tag_read_datetime;
            $reader_1_id = $nextTagRead->reader_id;
            $reader_1_name = $nextTagRead->reader_name;
            $reader_2_id = $tagRead->reader_id;
            $reader_2_name = $tagRead->reader_name;
        } elseif ($tagReadTimestamp < $nextTagReadTimestamp) {
            $direction = $tagReadPosition == RfidReaderPairing::CLOSE_TO_EXIT ? 'IN' : 'OUT';
            $tag_read_datetime = $tagReadPosition == RfidReaderPairing::CLOSE_TO_EXIT ?
                $nextTagRead->tag_read_datetime : $tagRead->tag_read_datetime;
            $reader_1_id = $tagRead->reader_id;
            $reader_1_name = $tagRead->reader_name;
            $reader_2_id = $nextTagRead->reader_id;
            $reader_2_name = $nextTagRead->reader_name;
        } else {
            return null;
        }

        return [
            'reader_1_id' => $reader_1_id,
            'reader_1_name' => $reader_1_name,
            'reader_2_id' => $reader_2_id,
            'reader_2_name' => $reader_2_name,
            'epc' => $tagRead->epc,
            'project_id' => $project->id,
            'project_name' => $project->name,
            'tag_read_datetime' => $tag_read_datetime,
            'direction' => $direction,
            'period' => $period,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function processStagingWorkerTimeLog($project, $period)
    {
        // get staging worker time log by project
        $stagingWorkerTimeLogs = StagingWorkerTimeLog::where('period', $period)
            ->where('project_id', $project->id)
            ->orderBy('tag_read_datetime')
            ->get();

        $timeLogsGroupByEpc = $stagingWorkerTimeLogs->groupBy('epc');

        $tempWorkerTimeLogs = [];
        foreach ($timeLogsGroupByEpc as $epc => $timeLogs) {
            $clockInTimeLog = $timeLogs->filter(function ($item) {
                return is_null($item['direction']) || $item['direction'] === 'IN';
            })->first();
            $clockOutTimeLog = $timeLogs->filter(function ($item) {
                return is_null($item['direction']) || $item['direction'] === 'OUT';
            })->last();
            $lastTimeLog = $timeLogs->last();

            if (isset($clockOutTimeLog) && isset($clockInTimeLog)) {
                $clockInTime = Carbon::parse($clockInTimeLog->tag_read_datetime);
                $clockOutTime = Carbon::parse($clockOutTimeLog->tag_read_datetime);
                $isClockOutLaterThanClockIn = $clockOutTime->greaterThan($clockInTime);
            }

            if (!isset($clockOutTimeLog) || (isset($clockInTimeLog) && isset($isClockOutLaterThanClockIn) && !$isClockOutLaterThanClockIn)) {
                $clockOutTimeLog = null;
            }

            $tempWorkerTimeLogs[] = $this->transformsToWorkerTimeLog($clockInTimeLog, $clockOutTimeLog, $lastTimeLog);
        }

        WorkerTimeLog::insert($tempWorkerTimeLogs);
    }

    public function getSummarizedTimeLogToTms($project, $date)
    {
        $summarizedWorkerTimeLog = WorkerTimeLog::select(
            'worker_time_logs.id',
            'period as cdate',
            'clock_in as cin',
            'clock_out as cout',
            'worker_time_logs.updated_at as updatedtime',
            'worker_time_logs.epc as rfid_tag_code',
            DB::raw('COALESCE(worker_time_logs.reader_2_name, worker_time_logs.reader_1_name) as rfid_reader_name'),
            'worker_time_logs.project_name as rfid_project',
            DB::raw('CASE WHEN r2location1.name IS NOT NULL THEN COALESCE(r2location1.name, "") ELSE COALESCE(r1location1.name, "") END as rfid_location1')         ,
            DB::raw('CASE WHEN r2location1.name IS NOT NULL THEN COALESCE(r2location2.name, "") ELSE COALESCE(r1location2.name, "") END as rfid_location2')         ,
            DB::raw('CASE WHEN r2location1.name IS NOT NULL THEN COALESCE(r2location3.name, "") ELSE COALESCE(r1location3.name, "") END as rfid_location3')         ,
            DB::raw('CASE WHEN r2location1.name IS NOT NULL THEN COALESCE(r2location4.name, "") ELSE COALESCE(r1location4.name, "") END as rfid_location4')         ,
        )->leftJoin('rfid_reader_managements as r2_rfid_reader_managements', 'worker_time_logs.reader_2_name', '=', 'r2_rfid_reader_managements.name')
            ->leftJoin('master_locations as r2location1', 'r2location1.id', '=', 'r2_rfid_reader_managements.location_1_id')
            ->leftJoin('master_locations as r2location2', 'r2location2.id', '=', 'r2_rfid_reader_managements.location_2_id')
            ->leftJoin('master_locations as r2location3', 'r2location3.id', '=', 'r2_rfid_reader_managements.location_3_id')
            ->leftJoin('master_locations as r2location4', 'r2location4.id', '=', 'r2_rfid_reader_managements.location_4_id')
        ->leftJoin('rfid_reader_managements as r1_rfid_reader_managements', 'worker_time_logs.reader_1_name', '=', 'r1_rfid_reader_managements.name')
            ->leftJoin('master_locations as r1location1', 'r1location1.id', '=', 'r1_rfid_reader_managements.location_1_id')
            ->leftJoin('master_locations as r1location2', 'r1location2.id', '=', 'r1_rfid_reader_managements.location_2_id')
            ->leftJoin('master_locations as r1location3', 'r1location3.id', '=', 'r1_rfid_reader_managements.location_3_id')
            ->leftJoin('master_locations as r1location4', 'r1location4.id', '=', 'r1_rfid_reader_managements.location_4_id')
            ->where('worker_time_logs.project_id', $project->id);

        if (isset($date)) {
            $date = Carbon::parse($date)->toDateString();
            $summarizedWorkerTimeLog->where('period', $date);
        }

        $staffs = DB::connection('tms_mysql')->table('staff')
        ->leftJoin('device_project','device_project.project','=','staff.location')
        ->where('device_project.device', $project->name)
        ->where('device_project.platform', 'RFID')
        ->get(['staff.code', 'staff.name', 'staff.location', 'staff.incharge', 'staff.rfid']);



        $staffMapping = $staffs->keyBy('rfid')->toArray();

        $summarizedWorkerTimeLog = $summarizedWorkerTimeLog->get()->map(function ($timeLog) use ($staffMapping) {
            $rfid = $timeLog['rfid_tag_code'];

            if (isset($staffMapping[$rfid])) {
                $staffInfo = $staffMapping[$rfid];

                $timeLog['staffcode'] = $staffInfo->code;
                $timeLog['staffname'] = $staffInfo->name;
                $timeLog['dept'] = $staffInfo->location;
                $timeLog['incharge'] = $staffInfo->incharge;
            }

            // convert clock in and clock out time to GMT+8
            $timeLog['cin'] = isset($timeLog['cin']) ? Carbon::parse($timeLog['cin'])->timezone('GMT+8') : null;
            $timeLog['cout'] = isset($timeLog['cout']) ? Carbon::parse($timeLog['cout'])->timezone('GMT+8') : null;
            $timeLog['updatedtime'] = Carbon::parse($timeLog['updatedtime'])->timezone('GMT+8');

            return $timeLog;
        });

        $summarizedWorkerTimeLog = $summarizedWorkerTimeLog->filter(function ($timeLog) {
             return isset($timeLog['staffcode']) && isset($timeLog['staffname']);
        });

        return $summarizedWorkerTimeLog;
    }

    public function insertSummarizedTimeLogToTms($summarizedWorkerTimeLog)
    {
        $successTimeLog = [];
        foreach ($summarizedWorkerTimeLog as $timeLog) {
            try {
                DB::connection('tms_mysql')->table('rosterdetail_rfid')->updateOrInsert(
                    [
                        'cdate' => $timeLog->cdate,
                        'rfid_tag_code' => $timeLog->rfid_tag_code,
                    ],
                    $timeLog->toArray()
                );
                $successTimeLog[] = $timeLog->id;
            } catch (\Exception $ex) {
                Log::error($ex->getMessage());
            }

        }

        WorkerTimeLog::whereIn('id', $successTimeLog)->update(['last_synced_to_tms' => Carbon::now()]);
    }

    public function sendSummarizedTimeLogToTms($project, $date)
    {
        $summarizedWorkerTimeLogs = $this->getSummarizedTimeLogToTms($project, $date);
        $this->insertSummarizedTimeLogToTms($summarizedWorkerTimeLogs);
    }
}
