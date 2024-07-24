<?php

namespace Tests\Unit\Service;

use App\Models\MasterLocation;
use App\Models\MasterProject;
use App\Models\RfidReaderManagement;
use App\Models\RfidReaderPairing;
use App\Models\RfidTagRead;
use App\Models\StagingWorkerTimeLog;
use App\Models\WorkerTimeLog;
use App\Services\WorkerTimeLogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerTimeLogServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WorkerTimeLogService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = app()->make(WorkerTimeLogService::class);
    }

    public function testProcessSingleReader_with_chosen_date_and_same_day()
    {
        $this->truncate();
        /**
         * project period in MY from: 7am - 10pm
         * ProjectSameDay daily_period_from 7am, daily_period_to 10pm
         *
         * 7 oclock  19 Feb 7am - 19 Feb 10.59pm MYT ------ 18 Feb 2300 - 19 Feb 1459 UTC
         *
         * 3 tag read for an epc
         * expect all tag read is inserted into staging_worker_time_logs
         */

        $mytPeriodFrom = '07:00:00';
        $mytPeriodTo = '22:59:59';

        $utcPeriodFrom = Carbon::parse($mytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $utcPeriodTo = Carbon::parse($mytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $chosenDate = '2024-02-19';

        $projectSameDay = MasterProject::factory()->create([
            'daily_period_from' => $utcPeriodFrom,
            'daily_period_to' => $utcPeriodTo,
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $epc1TagRead1 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse("2024-02-18 23:00:00"), // walk 7am MYT on chosenDate -> utc -> 2300
        ]);

        $epc1TagRead2 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse("2024-02-19 01:30:00"), // walk 930am MYT on chosenDate -> utc -> 0130
        ]);

        $epc1TagRead3 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse("2024-02-19 12:30:00"), // walk 730pm MYT on chosenDate -> utc -> 1230
        ]);

        $this->assertDatabaseCount('staging_worker_time_logs', 0);
        $this->assertDatabaseCount('worker_time_logs', 0);
        $this->artisan("cron:process-worker-time-log $chosenDate --staging");

        // ASSERT EPC ONE
        $this->assertDatabaseCount('staging_worker_time_logs', 3);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $chosenDate,
            'epc' => $epc1TagRead1->epc,
            'tag_read_datetime' => $epc1TagRead1->tag_read_datetime,
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $chosenDate,
            'epc' => $epc1TagRead2->epc,
            'tag_read_datetime' => $epc1TagRead2->tag_read_datetime,
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $chosenDate,
            'epc' => $epc1TagRead3->epc,
            'tag_read_datetime' => $epc1TagRead3->tag_read_datetime,
        ]);
    }

    public function testProcessSingleReader_with_default_date_and_same_day()
    {
        $this->truncate();
        /**
         * time is 20:30:00, default date
         * ProjectSameDay daily_period_from 8am, daily_period_to 7pm
         *
         * 2 tag read for an epc
         * expect all tag read is inserted into staging_worker_time_logs
         */
        $startingDate = now()->toDateString();

        $projectSameDay = MasterProject::factory()->create([
            'daily_period_from' => '08:00:00',
            'daily_period_to' => '19:00:00',
        ]);

        Carbon::setTestNow(Carbon::parse("$startingDate 20:30:00")); // time is set at 2030am

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $epc1TagRead1 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 08:30:00"), // walk 830 am
        ]);

        $epc1TagRead2 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 18:30:00"), // walk 630 am
        ]);

        $this->artisan("cron:process-worker-time-log --staging");

        // ASSERT EPC ONE
        $this->assertDatabaseCount('staging_worker_time_logs', 2);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead1->epc,
            'tag_read_datetime' => $epc1TagRead1->tag_read_datetime,
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead2->epc,
            'tag_read_datetime' => $epc1TagRead2->tag_read_datetime,
        ]);
    }

    public function testProcessSingleReader_with_chosen_date_and_cross_day()
    {
        $this->truncate();
        /**
         *
         * projectSameDay Cross day - chosen date
         * projectSameDay : daily_period_from 07:00:00, daily_period_to 06:59:59 --- Cross day chosen day
         *
         * 7 oclock -------------- 19 Feb 7am ====== 20 Feb 6.59am MYT ---------------- 18 Feb 2300 ====== 19 Feb 2259 UTC
         *
         */
        $mytPeriodFrom = '07:00:00';
        $mytPeriodTo = '06:59:59';

        $utcPeriodFrom = Carbon::parse($mytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $utcPeriodTo = Carbon::parse($mytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectSameDay = MasterProject::factory()->create([
            'daily_period_from' => $utcPeriodFrom,
            'daily_period_to' => $utcPeriodTo,
        ]);

        $chosenDate = '2024-02-19';

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $epc1TagRead1 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("2024-02-18 23:00:00"), // walk 7 am MYT on chosenDate -> utc -> 2300
        ]);

        $epc1TagRead2 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("2024-02-19 20:30:00"), // walk 230 am MYT on chosenDateAfter -> utc -> 2300
        ]);

        $epc1TagRead3 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("2024-02-19 23:30:00"), // walk 730 am -- this should be excluded because this is outside the period
        ]);

        $this->artisan("cron:process-worker-time-log $chosenDate --staging");

        // ASSERT EPC_TWO
        $this->assertDatabaseCount('staging_worker_time_logs', 2);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $chosenDate,
            'epc' => $epc1TagRead1->epc,
            'tag_read_datetime' => $epc1TagRead1->tag_read_datetime,
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $chosenDate,
            'epc' => $epc1TagRead2->epc,
            'tag_read_datetime' => $epc1TagRead2->tag_read_datetime,
        ]);

        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'period' => $chosenDate,
            'epc' => $epc1TagRead3->epc,
            'tag_read_datetime' => $epc1TagRead3->tag_read_datetime,
        ]);
    }

    public function testProcessSingleReader_with_default_date_and_cross_day()
    {
        $this->truncate();
        /**
         * time is 05:30:00, date is chosen
         *
         * ProjectCrossDay daily_period_from 6am, daily_period_to 4am
         *
         * 2 tag read for an epc
         * expect all tag read is inserted into staging_worker_time_logs
         */
        $endingDate = now()->toDateString();
        $startingDate = Carbon::parse($endingDate)->subDay()->toDateString();

        $projectSameDay = MasterProject::factory()->create([
            'daily_period_from' => '06:00:00',
            'daily_period_to' => '04:00:00',
        ]);

        Carbon::setTestNow(Carbon::parse("$endingDate 05:30:00")); // time is set at 0530am // so all project period_to is between 04:00:00 - 04:59:59

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $epc1TagRead0 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 05:30:00"), // walk 530am -- this should be excluded because not in same period
        ]);

        $epc1TagRead1 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 07:30:00"), // walk 7 am
        ]);

        $epc1TagRead2 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$endingDate 01:30:00"), // walk 130 am
        ]);

        $epc1TagRead3 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$endingDate 04:30:00"), // walk 430 am -- this should be excluded because not in same period
        ]);

        $this->artisan("cron:process-worker-time-log --staging");

        // ASSERT EPC_TWO
        $this->assertDatabaseCount('staging_worker_time_logs', 2);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead1->epc,
            'tag_read_datetime' => $epc1TagRead1->tag_read_datetime,
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead2->epc,
            'tag_read_datetime' => $epc1TagRead2->tag_read_datetime,
        ]);

        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead0->epc,
            'tag_read_datetime' => $epc1TagRead0->tag_read_datetime,
        ]);

        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead3->epc,
            'tag_read_datetime' => $epc1TagRead3->tag_read_datetime,
        ]);
    }

    public function testProcessSingleReader_pair_tag_reads_ignored()
    {
        $this->truncate();
        /**
         * mainProject - cross day - chosenDate - period_from 7am 6.59am
         *
         * 7 oclock  19 Feb 7am - 20 Feb 6.59am MYT ------ 18 Feb 2300 - 19 Feb 2259 UTC
         *
         * 2 existing tagRead in Database using a single reader
         * 2 existing tagRead in Database using a paired reader
         * 1 epc walk in/out 2 times - 2 tagRead
         * 1 epc walk in passing pairReader1 and pairReader2
         * Expect 2 records will be entered into staging_worker_time_logs, pair tag_read is ignored
         *
         */
         $mytPeriodFrom = '07:00:00';
         $mytPeriodTo = '06:59:59';

         $utcPeriodFrom = Carbon::parse($mytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
         $utcPeriodTo = Carbon::parse($mytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $mainProject = MasterProject::factory()->create([
            'daily_period_from' => $utcPeriodFrom,
            'daily_period_to' => $utcPeriodTo,
        ]);

        $chosenDate = '2024-02-19';

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader1 = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader2 = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairId = 1;

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $pairReader1->id,
            'reader_position' => 1,
        ]);

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $pairReader2->id,
            'reader_position' => 2,
        ]);

        $epc1TagRead1 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-18 23:30:00'), // 730am in chosenDate -> utc -> 2330
        ]);

        $epc1TagRead2 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 02:30:00'), // 1030am in chosenDate -> utc -> 0230
        ]);

        // EPC_ONE walk passed pairReader1
        $epc1PairReader1 = RfidTagRead::factory()->create([
            'reader_name' => $pairReader1->name,
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-18 23:30:00'), // 730am in chosenDate -> utc -> 2330
        ]);

        // EPC_ONE walk passed pairReader2
        $epc1PairReader2 = RfidTagRead::factory()->create([
            'reader_name' => $pairReader2->name,
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-19 02:30:00'), // 1030am in chosenDate -> utc -> 0230
        ]);

        $project = $this->service->getPeriodByProject($mainProject, $chosenDate);

        // ACT
        $this->service->processSingleReader($project['project'], $project['startingPeriod'], $project['endingPeriod'], $project['period']);

        $this->assertDatabaseCount('staging_worker_time_logs', 2);

        // Assert EPC_ONE SingleReader
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $chosenDate,
            'epc' => $epc1TagRead1->epc,
            'tag_read_datetime' => $epc1TagRead1->tag_read_datetime,
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $chosenDate,
            'epc' => $epc1TagRead2->epc,
            'tag_read_datetime' => $epc1TagRead2->tag_read_datetime,
        ]);

        // Assert EPC_ONE PairReader
        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'reader_1_name' => $epc1PairReader1->reader_name,
            'period' => $chosenDate,
            'epc' => $pairReader1->epc,
            'tag_read_datetime' => $pairReader1->tag_read_datetime,
        ]);

        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'reader_2_name' => $epc1PairReader2->reader_name,
            'period' => $chosenDate,
            'epc' => $epc1PairReader2->epc,
            'tag_read_datetime' => $epc1PairReader2->tag_read_datetime,
        ]);
    }

    public function testProcessStagingWorkerTimeLog_for_single_reader()
    {
        $this->truncate();
        /**
         * mainProject daily_period_from : 6am, daily_period_to : 5:59am
         *
         * 4 staging record for epc_a, record is first walk in, lunch walk in,lunch walk out and last walk out
         * 2 staging record for epc_b, record is first walk in and last walk out
         * Expect clock in, clock out will show staging record for first and last walk out respectively for the epc_a and epc_b
         *
         */
        $period = '2024-02-01';

        $mainProject = MasterProject::factory()->create([
            'daily_period_from' => '06:00:00',
            'daily_period_to' => '05:59:00',
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        /**
         * epc_A
         */
        $epcA =  fake()->regexify('[A-Z]{5}[0-9]{3}');
        $epcAFirstWalkIn = Carbon::parse("$period 07:30:00")->toDateTimeString(); // 730am walk in
        $epcALastWalkOut = Carbon::parse("$period 18:30:00")->toDateTimeString(); // 1830am walk out

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $singleReader->id,
            'reader_1_name' => $singleReader->name,
            'reader_2_id' => null,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $epcA,
            'tag_read_datetime' => $epcAFirstWalkIn, // 730am walk in
            'direction' => null,
            'period' => $period,
        ]);

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $singleReader->id,
            'reader_1_name' => $singleReader->name,
            'reader_2_id' => null,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $epcA,
            'tag_read_datetime' => Carbon::parse("$period 12:30:00"), // 1230am walk out
            'direction' => null,
            'period' => $period,
        ]);

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $singleReader->id,
            'reader_1_name' => $singleReader->name,
            'reader_2_id' => null,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $epcA,
            'tag_read_datetime' => Carbon::parse("$period 13:30:00"), // 1330am walk in
            'direction' => null,
            'period' => $period,
        ]);

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $singleReader->id,
            'reader_1_name' => $singleReader->name,
            'reader_2_id' => null,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $epcA,
            'tag_read_datetime' => $epcALastWalkOut, // 1830am walk out
            'direction' => null,
            'period' => $period,
        ]);

        /**
         * epc_b
         */
        $epcB =  fake()->regexify('[A-Z]{5}[0-9]{3}');
        $epcBFirstWalkIn = Carbon::parse("$period 08:30:00")->toDateTimeString(); // 830am walk in
        $epcBLastWalkOut = Carbon::parse("$period 19:30:00")->toDateTimeString(); // 1930am walk out

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $singleReader->id,
            'reader_1_name' => $singleReader->name,
            'reader_2_id' => null,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $epcB,
            'tag_read_datetime' => $epcBFirstWalkIn, // 830am walk in
            'direction' => null,
            'period' => $period,
        ]);

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $singleReader->id,
            'reader_1_name' => $singleReader->name,
            'reader_2_id' => null,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $epcB,
            'tag_read_datetime' => $epcBLastWalkOut, // 1930am walk out
            'direction' => null,
            'period' => $period,
        ]);

        // ACT
        $this->service->processStagingWorkerTimeLog($mainProject, $period);

        // ASSERT - epcA
        $this->assertDatabaseHas('worker_time_logs', [
            'reader_1_id' => $singleReader->id, // firstReader detected
            'reader_1_name' => $singleReader->name, // firstReader detected
            'reader_2_id' => $singleReader->id, // lastReader detected
            'reader_2_name' => $singleReader->name, // lastReader detected
            'epc' => $epcA,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'clock_in' => $epcAFirstWalkIn,
            'clock_out' => $epcALastWalkOut,
            'last_tag_read' => $epcALastWalkOut,
        ]);

        // ASSERT - epcB
        $this->assertDatabaseHas('worker_time_logs', [
            'reader_1_id' => $singleReader->id, // firstReader detected
            'reader_1_name' => $singleReader->name, // firstReader detected
            'reader_2_id' => $singleReader->id, // lastReader detected
            'reader_2_name' => $singleReader->name, // lastReader detected
            'epc' => $epcB,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'clock_in' => $epcBFirstWalkIn,
            'clock_out' => $epcBLastWalkOut,
            'last_tag_read' => $epcBLastWalkOut,
        ]);
    }

    public function test_process_staging_worker_time_log_for_pair_reader_has_clock_in_and_clock_out()
    {
        $this->truncate();
        /**
         *
         * assume reader A and reader B is paired => reader A is position 1 (closer to exit) and reader B is position 2 (closer to site)
         *
         * In case there is clock in and clock out during the period:
         *
         * user walk pass reader A at 7.00am -> then walk pass reader B at 7.01am => direction IN -> take reader B time (7.01am)
         * user walk pass reader B at 6.00pm -> then walk pass reader A at 6.01pm => direction OUT -> take reader A time (6.01pm)
         *
         */

        /**
         * mainProject daily_period_from : 6am, daily_period_to : 5:59am
         *
         * 2 staging record for epc_a, record is first walk in, last walk out using pair reader
         *
         */
        $period = '2024-02-01';

        $mainProject = MasterProject::factory()->create([
            'daily_period_from' => '06:00:00',
            'daily_period_to' => '05:59:00',
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $pairReader1 = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader2 = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairId = 1;

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $pairReader1->id,
            'reader_position' => 1,
        ]);

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $pairReader2->id,
            'reader_position' => 2,
        ]);

        /**
         * epc_A
         */
        $epcA =  fake()->regexify('[A-Z]{5}[0-9]{3}');
        $epcAFirstWalkIn = Carbon::parse("$period 07:30:00")->toDateTimeString(); // 730am walk in
        $epcALastWalkOut = Carbon::parse("$period 18:30:00")->toDateTimeString(); // 1830am walk out

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $pairReader1->id, // detect pairReader1 first
            'reader_1_name' => $pairReader1->name,
            'reader_2_id' => $pairReader2->id, // detect pairReader2 second
            'reader_2_name' => $pairReader2->name,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $epcA,
            'tag_read_datetime' => $epcAFirstWalkIn, // 730am walk in
            'direction' => 'IN', // going in
            'period' => $period,
        ]);

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $pairReader2->id, // detect pairReader2 first
            'reader_1_name' => $pairReader2->name,
            'reader_2_id' => $pairReader1->id, // detect pairReader1 second
            'reader_2_name' => $pairReader1->name,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $epcA,
            'tag_read_datetime' => $epcALastWalkOut, // 1830am walk out
            'direction' => 'OUT', // going out
            'period' => $period,
        ]);

        // ACT
        $this->service->processStagingWorkerTimeLog($mainProject, $period);

        // ASSERT - epcA
        $this->assertDatabaseHas('worker_time_logs', [
            'reader_1_id' => $pairReader2->id,
            'reader_1_name' => $pairReader2->name,
            'reader_2_id' => $pairReader1->id,
            'reader_2_name' => $pairReader1->name,
            'epc' => $epcA,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'clock_in' => $epcAFirstWalkIn,
            'clock_out' => $epcALastWalkOut,
            'last_tag_read' => $epcALastWalkOut,
        ]);
    }

    public function test_process_staging_worker_time_log_for_pair_reader_only_clock_in()
    {
        $this->truncate();
        /**
         *
         * assume reader A and reader B is paired => reader A is position 1 (closer to exit) and reader B is position 2 (closer to site)
         *
         * In case only clock in but no clock out
         *
         * user walk pass reader A at 7.00am -> then walk pass reader B at 7.01am => direction IN -> take reader B time (7.01am)
         *
         */

        /**
         * mainProject daily_period_from : 6am, daily_period_to : 5:59am
         *
         * 2 staging record for epc_a, record is first walk in, last walk out using pair reader
         *
         */
        $period = '2024-02-01';

        $mainProject = MasterProject::factory()->create([
            'daily_period_from' => '06:00:00',
            'daily_period_to' => '05:59:00',
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $pairReader1 = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader2 = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairId = 1;

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $pairReader1->id,
            'reader_position' => 1,
        ]);

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $pairReader2->id,
            'reader_position' => 2,
        ]);

        /**
         * epc_B -> only clock in for the period
         */
        $epcB =  fake()->regexify('[A-Z]{5}[0-9]{3}');
        $epcBFirstWalkIn = Carbon::parse("$period 07:30:02")->toDateTimeString(); // 730am only walk in

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $pairReader1->id, // detect pairReader1 first
            'reader_1_name' => $pairReader1->name,
            'reader_2_id' => $pairReader2->id, // detect pairReader2 second
            'reader_2_name' => $pairReader2->name,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $epcB,
            'tag_read_datetime' => $epcBFirstWalkIn, // 730am walk in
            'direction' => 'IN', // going in
            'period' => $period,
        ]);

        // ACT
        $this->service->processStagingWorkerTimeLog($mainProject, $period);

        // ASSERT - epcB
        $this->assertDatabaseHas('worker_time_logs', [
            'reader_1_id' => $pairReader2->id,
            'reader_1_name' => $pairReader2->name,
            'reader_2_id' => null,
            'reader_2_name' => null,
            'epc' => $epcB,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'clock_in' => $epcBFirstWalkIn,
            'clock_out' => null,
            'last_tag_read' => $epcBFirstWalkIn,
        ]);
    }

    public function test_process_staging_worker_time_log_for_pair_reader_only_clock_out()
    {
        $this->truncate();
        /**
         *
         * assume reader A and reader B is paired => reader A is position 1 (closer to exit) and reader B is position 2 (closer to site)
         *
         * In case only clock out but no clock in
         *
         * user walk pass reader B at 8.00pm -> then walk pass reader A at 8.01pm => direction OUT -> take reader B time (8.01pm)
         *
         */

        /**
         * mainProject daily_period_from : 6am, daily_period_to : 5:59am
         *
         * 2 staging record for epc_a, record is first walk in, last walk out using pair reader
         *
         */
        $period = '2024-02-01';

        $mainProject = MasterProject::factory()->create([
            'daily_period_from' => '06:00:00',
            'daily_period_to' => '05:59:00',
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $pairReader1 = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader2 = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairId = 1;

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $pairReader1->id,
            'reader_position' => 1,
        ]);

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $pairReader2->id,
            'reader_position' => 2,
        ]);

        /**
         * epcC -> only clock out for the period
         */
        $epcC =  'A1';
        $earlierWalkTime = Carbon::parse("$period 19:01:01")->toDateTimeString(); // 7:01:01pm
        $epcCLastWalkOut = Carbon::parse("$period 19:01:02")->toDateTimeString(); // 7:01:02pm only walk out

        $this->assertDatabaseCount('staging_worker_time_logs',0);

        // Add another staging record where clock out is earlier than clock in, causing 1 of the bug where
        // setting clock out = null

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => 5, // detect pairReader2 first
            'reader_1_name' => 5,
            'reader_2_id' => 6, // detect pairReader1 second
            'reader_2_name' => 6,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => 'A0',
            'tag_read_datetime' => $epcCLastWalkOut, // 7.01pm only walk out
            'direction' => 'IN', // going out
            'period' => $period,
        ]);

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => 5, // detect pairReader2 first
            'reader_1_name' => 5,
            'reader_2_id' => 6, // detect pairReader1 second
            'reader_2_name' => 6,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => 'A0',
            'tag_read_datetime' => $earlierWalkTime, // 7.01pm only walk out
            'direction' => 'OUT', // going out
            'period' => $period,
        ]);

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $pairReader2->id, // detect pairReader2 first
            'reader_1_name' => $pairReader2->name,
            'reader_2_id' => $pairReader1->id, // detect pairReader1 second
            'reader_2_name' => $pairReader1->name,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $epcC,
            'tag_read_datetime' => $epcCLastWalkOut, // 7.01pm only walk out
            'direction' => 'OUT', // going out
            'period' => $period,
        ]);

        $this->assertDatabaseCount('staging_worker_time_logs',3);

        $this->assertDatabaseMissing('worker_time_logs', [
            'reader_1_id' => null,
            'reader_1_name' => null,
            'reader_2_id' => $pairReader1->id,
            'reader_2_name' => $pairReader1->name,
            'epc' => $epcC,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'clock_in' => null,
            'clock_out' => $epcCLastWalkOut,
            'last_tag_read' => $epcCLastWalkOut,
        ]);

        // ACT
        $this->service->processStagingWorkerTimeLog($mainProject, $period);

        // ASSERT - epcC
        $this->assertDatabaseHas('worker_time_logs', [
            'reader_1_id' => null,
            'reader_1_name' => null,
            'reader_2_id' => $pairReader1->id,
            'reader_2_name' => $pairReader1->name,
            'epc' => $epcC,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'clock_in' => null,
            'clock_out' => $epcCLastWalkOut,
            'last_tag_read' => $epcCLastWalkOut,
        ]);
    }

    public function test_process_staging_worker_time_hybrid_with_walk_in_single_reader_and_walk_out_pair_reader()
    {
        $this->truncate();
        /**
         * project period in MY from: 7am - 6.59am
         * ProjectCrossDay daily_period_from 7am, daily_period_to 6.59am
         * user walk in thru single reader 8am
         * later walk out thru paired reader 6pm
         *
         */
        $period = '2024-02-01';

        $mainProject = MasterProject::factory()->create([
            'daily_period_from' => '07:00:00',
            'daily_period_to' => '06:59:00',
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader1 = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader2 = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairId = 1;

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $pairReader1->id,
            'reader_position' => 1,
        ]);

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $pairReader2->id,
            'reader_position' => 2,
        ]);

        /**
         * user walk in thru single reader 8am
         * later walk out thru paired reader 6pm
         */
        $hybridUser =  fake()->regexify('[A-Z]{5}[0-9]{3}');
        $hybridUserWalkIn = Carbon::parse("$period 08:00:00")->toDateTimeString(); // 8am
        $hybridUserWalkOut = Carbon::parse("$period 18:00:00")->toDateTimeString(); // 6pm walk out

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $singleReader->id,
            'reader_1_name' => $singleReader->name,
            'reader_2_id' => null,
            'reader_2_name' => null,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $hybridUser,
            'tag_read_datetime' => $hybridUserWalkIn,
            'direction' => null,
            'period' => $period,
        ]);

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $pairReader2->id, // detect pairReader2 first
            'reader_1_name' => $pairReader2->name,
            'reader_2_id' => $pairReader1->id, // detect pairReader1 second
            'reader_2_name' => $pairReader1->name,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $hybridUser,
            'tag_read_datetime' => $hybridUserWalkOut, // 6pm walk out
            'direction' => 'OUT', // going out
            'period' => $period,
        ]);

        // ACT
        $this->service->processStagingWorkerTimeLog($mainProject, $period);

        // ASSERT - hybridUser
        $this->assertDatabaseHas('worker_time_logs', [
            'reader_1_id' => $singleReader->id,
            'reader_1_name' => $singleReader->name,
            'reader_2_id' => $pairReader1->id, // detect pairReader1 second
            'reader_2_name' => $pairReader1->name,
            'epc' => $hybridUser,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'clock_in' => $hybridUserWalkIn,
            'clock_out' => $hybridUserWalkOut,
            'last_tag_read' => $hybridUserWalkOut,
        ]);
    }

    public function test_process_staging_worker_time_hybrid_with_walk_in_pair_reader_and_walk_out_single_reader()
    {
        $this->truncate();
        /**
         * project period in MY from: 7am - 6.59am
         * ProjectCrossDay daily_period_from 7am, daily_period_to 6.59am
         * user walk in thru paired reader 8am
         * later walk out thru single reader 6pm
         *
         */
        $period = '2024-02-01';

        $mainProject = MasterProject::factory()->create([
            'daily_period_from' => '07:00:00',
            'daily_period_to' => '06:59:00',
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader1 = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader2 = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairId = 1;

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $pairReader1->id,
            'reader_position' => 1,
        ]);

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $pairReader2->id,
            'reader_position' => 2,
        ]);

        /**
         * user walk in thru paired reader 8am
         * later walk out thru single reader 6pm
         */
        $hybridUser =  fake()->regexify('[A-Z]{5}[0-9]{3}');
        $hybridUserWalkIn = Carbon::parse("$period 08:00:00")->toDateTimeString(); // 8am
        $hybridUserWalkOut = Carbon::parse("$period 18:00:00")->toDateTimeString(); // 6pm walk out

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $pairReader1->id, // detect pairReader1 first
            'reader_1_name' => $pairReader1->name,
            'reader_2_id' => $pairReader2->id, // detect pairReader2 second
            'reader_2_name' => $pairReader2->name,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $hybridUser,
            'tag_read_datetime' => $hybridUserWalkIn,
            'direction' => 'IN', // going in
            'period' => $period,
        ]);

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $singleReader->id,
            'reader_1_name' => $singleReader->name,
            'reader_2_id' => null,
            'reader_2_name' => null,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $hybridUser,
            'tag_read_datetime' => $hybridUserWalkOut,
            'direction' => null,
            'period' => $period,
        ]);

        // ACT
        $this->service->processStagingWorkerTimeLog($mainProject, $period);

        // ASSERT - hybridUser
        $this->assertDatabaseHas('worker_time_logs', [
            'reader_1_id' => $pairReader2->id,
            'reader_1_name' => $pairReader2->name,
            'reader_2_id' => $singleReader->id,
            'reader_2_name' => $singleReader->name,
            'epc' => $hybridUser,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'clock_in' => $hybridUserWalkIn,
            'clock_out' => $hybridUserWalkOut,
            'last_tag_read' => $hybridUserWalkOut,
        ]);
    }

    public function test_process_staging_worker_time_hybrid_invalid_walk_out_with_paired_but_valid_walk_in_with_single()
    {
        $this->truncate();
        /**
         * project period in MY from: 7am - 6.59am
         * ProjectCrossDay daily_period_from 7am, daily_period_to 6.59am
         * user walk out using paired readers 4.36pm
         * later walk in using single reader 5.29pm
         *
         */
        $period = '2024-02-01';

        $mainProject = MasterProject::factory()->create([
            'daily_period_from' => '07:00:00',
            'daily_period_to' => '06:59:00',
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader1 = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader2 = RfidReaderManagement::factory()->create([
            'project_id' => $mainProject->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairId = 1;

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $pairReader1->id,
            'reader_position' => 1,
        ]);

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $pairReader2->id,
            'reader_position' => 2,
        ]);

        /**
         * user walk out using paired readers 4.36pm
         * later walk in using single reader 5.29pm
         */
        $hybridUser =  fake()->regexify('[A-Z]{5}[0-9]{3}');
        $hybridUserWalkOut = Carbon::parse("$period 16:36:00")->toDateTimeString(); // 4.36pm
        $hybridUserWalkIn = Carbon::parse("$period 17:29:00")->toDateTimeString(); // 5.29pm walk out

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $pairReader2->id, // detect pairReader2 first
            'reader_1_name' => $pairReader2->name,
            'reader_2_id' => $pairReader1->id, // detect pairReader1 second
            'reader_2_name' => $pairReader1->name,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $hybridUser,
            'tag_read_datetime' => $hybridUserWalkOut,
            'direction' => 'OUT', // going out
            'period' => $period,
        ]);

        StagingWorkerTimeLog::factory()->create([
            'reader_1_id' => $singleReader->id,
            'reader_1_name' => $singleReader->name,
            'reader_2_id' => null,
            'reader_2_name' => null,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'epc' => $hybridUser,
            'tag_read_datetime' => $hybridUserWalkIn,
            'direction' => null,
            'period' => $period,
        ]);

        // ACT
        $this->service->processStagingWorkerTimeLog($mainProject, $period);

        // ASSERT - hybridUser
        $this->assertDatabaseHas('worker_time_logs', [
            'reader_1_id' => $singleReader->id,
            'reader_1_name' => $singleReader->name,
            'reader_2_id' => null,
            'reader_2_name' => null,
            'epc' => $hybridUser,
            'project_id' => $mainProject->id,
            'project_name' => $mainProject->name,
            'clock_in' => $hybridUserWalkIn,
            'clock_out' => null,
            'last_tag_read' => $hybridUserWalkIn,
        ]);
    }

    public function test_process_double_reader_with_chosen_date_and_same_day()
    {
        $this->truncate();

        $chosenDate = '2024-02-01';
        $currentDate = '2024-03-01';

        $projectSameDay = MasterProject::factory()->create([
            'daily_period_from' => '00:00:00',
            'daily_period_to' => '23:59:59',
        ]);

        Carbon::setTestNow(Carbon::parse("$currentDate 23:30:00")); // time is set at 2330am

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $reader1 = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $reader2 = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader1->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_EXIT]);
        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader2->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_SITE]);

        // walk in
        $epc1TagRead1 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse("$chosenDate 07:30:00"),
        ]);
        $epc1TagRead2 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse("$chosenDate 07:30:05"),
        ]);

        // walk out
        $epc1TagRead3 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse("$chosenDate 19:30:00"),
        ]);

        $epc1TagRead4 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse("$chosenDate 19:30:05"),
        ]);

        $this->assertDatabaseCount('staging_worker_time_logs', 0);
        $this->assertDatabaseCount('worker_time_logs', 0);
        $this->artisan("cron:process-worker-time-log $chosenDate --staging");

        // ASSERT EPC ONE
        $this->assertDatabaseCount('staging_worker_time_logs', 2);
        $this->assertDatabaseCount('worker_time_logs', 1);


        $this->assertDatabaseHas('staging_worker_time_logs', [
            'direction' => 'IN',
            'tag_read_datetime' => '2024-02-01 07:30:00'
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'direction' => 'OUT',
            'tag_read_datetime' => '2024-02-01 19:30:05'
        ]);
    }

    public function test_processing_double_reader_with_default_date_and_same_day()
    {
        $this->truncate();
        /**
         * time is 20:30:00, default date
         * ProjectSameDay daily_period_from 8am, daily_period_to 7pm
         *
         * 2 tag read for an epc
         * expect all tag read is inserted into staging_worker_time_logs
         */
        $startingDate = now()->toDateString();

        $projectSameDay = MasterProject::factory()->create([
            'daily_period_from' => '08:00:00',
            'daily_period_to' => '19:00:00',
        ]);

        Carbon::setTestNow(Carbon::parse("$startingDate 20:30:00")); // time is set at 2030am

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $reader1 = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $reader2 = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader1->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_EXIT]);
        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader2->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_SITE]);

        $epc1TagRead1 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 08:30:00"), // walk 830 am
        ]);

        $epc1TagRead2 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 08:30:01"), // walk 830 am
        ]);

        $epc1TagRead3 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 18:30:00"), // walk 630 am
        ]);

        $epc1TagRead4 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 18:30:01"), // walk 630 am
        ]);

        $this->assertDatabaseCount('staging_worker_time_logs', 0);
        $this->assertDatabaseCount('worker_time_logs', 0);
        $this->artisan("cron:process-worker-time-log --staging");

        // ASSERT EPC ONE
        $this->assertDatabaseCount('staging_worker_time_logs', 2);
        $this->assertDatabaseCount('worker_time_logs', 1);


        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead1->epc,
            'tag_read_datetime' => $epc1TagRead1->tag_read_datetime,
            'direction' => 'IN'
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead4->epc,
            'tag_read_datetime' => $epc1TagRead4->tag_read_datetime,
            'direction' => 'OUT'
        ]);
    }

    public function test_processing_double_reader_with_default_date_and_same_day_with_undebounced_data()
    {
        $this->truncate();
        /**
         * time is 20:30:00, default date
         * ProjectSameDay daily_period_from 8am, daily_period_to 7pm
         *
         * 2 tag read for an epc
         * expect all tag read is inserted into staging_worker_time_logs
         */
        $startingDate = now()->toDateString();

        $projectSameDay = MasterProject::factory()->create([
            'daily_period_from' => '08:00:00',
            'daily_period_to' => '19:00:00',
        ]);

        Carbon::setTestNow(Carbon::parse("$startingDate 20:30:00")); // time is set at 2030am

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $reader1 = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $reader2 = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader1->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_EXIT]);
        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader2->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_SITE]);

        $epc1TagRead1 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 08:30:00"), // walk 830 am
        ]);

        $epc1TagRead2 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 08:30:01"), // walk 830 am
        ]);

        $epc1TagRead3 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 18:30:00"), // walk 630 am
        ]);

        $epc1TagRead4 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 18:30:01"), // walk 630 am
        ]);

        // add tag read
        $epc1TagRead5 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 18:30:03"), // walk 630 am
        ]);

        $this->artisan("cron:process-worker-time-log --staging");

        // ASSERT EPC ONE
        $this->assertDatabaseCount('staging_worker_time_logs', 3);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead1->epc,
            'tag_read_datetime' => $epc1TagRead1->tag_read_datetime,
            'direction' => 'IN'
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead4->epc,
            'tag_read_datetime' => $epc1TagRead4->tag_read_datetime,
            'direction' => 'OUT'
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead4->epc,
            'tag_read_datetime' => $epc1TagRead5->tag_read_datetime,
            'direction' => 'OUT'
        ]);
    }

    public function test_processing_double_reader_with_chosen_date_and_cross_day()
    {
        $this->truncate();
        /**
         * time is 05:30:00, date is chosen
         * ProjectCrossDay daily_period_from 7am, daily_period_to 5am
         *
         * 2 tag read for an epc
         * expect all tag read is inserted into staging_worker_time_logs
         */

        $chosenDate = '2024-03-01';
        $chosenDateAfter = Carbon::parse($chosenDate)->addDay()->toDateString();

        $projectSameDay = MasterProject::factory()->create([
            'daily_period_from' => '07:00:00',
            'daily_period_to' => '05:00:00',
        ]);

        Carbon::setTestNow(Carbon::parse("$chosenDate 05:30:00")); // time is set at 0430am

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $reader1 = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $reader2 = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader1->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_EXIT]);
        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader2->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_SITE]);

        $epc1TagRead1 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$chosenDate 07:30:00"), // walk 7 am
        ]);

        $epc1TagRead2 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$chosenDate 07:30:05"), // walk 7 am
        ]);

        $epc1TagRead3 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$chosenDateAfter 02:30:00"), // walk 230 am
        ]);

        $epc1TagRead4 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$chosenDateAfter 02:30:05"), // walk 230 am
        ]);

        $epc1TagRead5 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$chosenDateAfter 06:30:00"), // walk 630 am -- this should be excluded because this is outside the period
        ]);

        $epc1TagRead6 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$chosenDateAfter 06:30:05"), // walk 630 am -- this should be excluded because this is outside the period
        ]);

        $this->artisan("cron:process-worker-time-log $chosenDate --staging");

        // ASSERT EPC_TWO
        $this->assertDatabaseCount('staging_worker_time_logs', 2);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $chosenDate,
            'epc' => $epc1TagRead1->epc,
            'tag_read_datetime' => $epc1TagRead1->tag_read_datetime,
            'direction' => 'IN'
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $chosenDate,
            'epc' => $epc1TagRead4->epc,
            'tag_read_datetime' => $epc1TagRead4->tag_read_datetime,
            'direction' => 'OUT'
        ]);

        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'period' => $chosenDate,
            'epc' => $epc1TagRead5->epc,
            'tag_read_datetime' => $epc1TagRead5->tag_read_datetime,
        ]);

        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'period' => $chosenDate,
            'epc' => $epc1TagRead5->epc,
            'tag_read_datetime' => $epc1TagRead6->tag_read_datetime,
        ]);
    }

    public function test_processing_double_reader_with_default_date_and_cross_day()
    {
        $this->truncate();
        /**
         * time is 05:30:00, date is chosen
         * ProjectCrossDay daily_period_from 6am, daily_period_to 4am
         *
         * 2 tag read for an epc
         * expect all tag read is inserted into staging_worker_time_logs
         */
        $endingDate = now()->toDateString();
        $startingDate = Carbon::parse($endingDate)->subDay()->toDateString();

        $projectSameDay = MasterProject::factory()->create([
            'daily_period_from' => '06:00:00',
            'daily_period_to' => '04:00:00',
        ]);

        Carbon::setTestNow(Carbon::parse("$endingDate 05:30:00")); // time is set at 0530am

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $reader1 = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $reader2 = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader1->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_EXIT]);
        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader2->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_SITE]);

        $epc1TagRead1 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 05:30:00"), // walk 530am -- this should be excluded because not in same period
        ]);

        $epc1TagRead2 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 05:30:05"), // walk 530am -- this should be excluded because not in same period
        ]);

        $epc1TagRead3 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 07:30:00"), // walk 7 am
        ]);

        $epc1TagRead4 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$startingDate 07:30:05"), // walk 7 am
        ]);

        $epc1TagRead5 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$endingDate 01:30:00"), // walk 130 am
        ]);

        $epc1TagRead6 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$endingDate 01:30:05"), // walk 130 am
        ]);

        $epc1TagRead7 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$endingDate 04:30:00"), // walk 430 am -- this should be excluded because not in same period
        ]);

        $epc1TagRead8 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse("$endingDate 04:30:05"), // walk 430 am -- this should be excluded because not in same period
        ]);

        $this->artisan("cron:process-worker-time-log --staging");

        // ASSERT EPC_TWO
        $this->assertDatabaseCount('staging_worker_time_logs', 2);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead5->epc,
            'tag_read_datetime' => $epc1TagRead5->tag_read_datetime,
            'direction' => 'IN'
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead4->epc,
            'tag_read_datetime' => $epc1TagRead4->tag_read_datetime,
        ]);

        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead1->epc,
            'tag_read_datetime' => $epc1TagRead1->tag_read_datetime,
        ]);

        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead2->epc,
            'tag_read_datetime' => $epc1TagRead2->tag_read_datetime,
        ]);

        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead7->epc,
            'tag_read_datetime' => $epc1TagRead7->tag_read_datetime,
        ]);

        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'period' => $startingDate,
            'epc' => $epc1TagRead8->epc,
            'tag_read_datetime' => $epc1TagRead8->tag_read_datetime,
        ]);
    }

    public function test_process_double_reader_with_no_proper_second_reader()
    {
        $this->truncate();
        $chosenDate = '2024-02-01';
        $currentDate = '2024-03-01';

        $projectSameDay = MasterProject::factory()->create([
            'daily_period_from' => '00:00:00',
            'daily_period_to' => '23:59:59',
        ]);

        Carbon::setTestNow(Carbon::parse("$currentDate 23:30:00")); // time is set at 2330am

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $reader1 = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $reader2 = RfidReaderManagement::factory()->create([
            'project_id' => $projectSameDay->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader1->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_EXIT]);
        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader2->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_SITE]);

        // walk in
        $epc1TagRead1 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse("$chosenDate 07:30:00"),
        ]);

        // walk out
        $epc1TagRead3 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse("$chosenDate 19:30:00"),
        ]);

        $this->artisan("cron:process-worker-time-log --staging");

        // ASSERT EPC ONE
        $this->assertDatabaseCount('staging_worker_time_logs', 0);
    }

    public function testGetPeriodByProject()
    {
        $this->truncate();
        /**
         * in UTC
         *
         * Project A : daily_period_from 05:00:00, daily_period_to 03:59:59 --- Cross day
         * Project B : daily_period_from 03:00:00, daily_period_to 02:59:59 --- Cross day
         * Project C : daily_period_from 00:00:00, daily_period_to 23:59:59 --- Same day
         * Project D : daily_period_from 08:00:00, daily_period_to 18:59:59 --- Same day
         *
         */

        $projectA = MasterProject::factory()->create([
            'name' => 'Project A',
            'daily_period_from' => '05:00:00',
            'daily_period_to' => '03:59:59',
        ]);

        $projectB = MasterProject::factory()->create([
            'name' => 'Project B',
            'daily_period_from' => '03:00:00',
            'daily_period_to' => '02:59:59',
        ]);

        $projectC = MasterProject::factory()->create([
            'name' => 'Project C',
            'daily_period_from' => '00:00:00',
            'daily_period_to' => '23:59:59',
        ]);

        $projectD = MasterProject::factory()->create([
            'name' => 'Project D',
            'daily_period_from' => '08:00:00',
            'daily_period_to' => '18:59:59',
        ]);

        /**
         *
         * Project A cross day - default date
         * Project A : daily_period_from 05:00:00, daily_period_to 03:59:59 --- Cross day
         * 19 Feb 05:00:00, 20 Feb 03:59:59 --- Cross day
         *
         */

        Carbon::setTestNow(Carbon::parse('2024-02-20 04:30:00')); // time is after projectA period has ended

        $startingDate = now()->subDay()->toDateString();
        $endingDate = now()->toDateString();

        $startingPeriod = Carbon::parse("$startingDate $projectA->daily_period_from")->toDateTimeString();
        $endingPeriod = Carbon::parse("$endingDate $projectA->daily_period_to")->toDateTimeString();

        $result = $this->service->getPeriodByProject($projectA, null);

        $this->assertEquals($startingPeriod, $result['startingPeriod']);
        $this->assertEquals($endingPeriod, $result['endingPeriod']);
        $this->assertEquals($startingDate, $result['period']);

        /**
         *
         * Project B cross day - chosen date
         * Project B : daily_period_from 03:00:00, daily_period_to 02:59:59 --- Cross day
         *
         */
        $chosenDate = Carbon::parse('2024-02-01');

        $startingDate = $chosenDate->toDateString();
        $endingDate = $chosenDate->addDay()->toDateString();

        $startingPeriod = Carbon::parse("$startingDate $projectB->daily_period_from")->toDateTimeString();
        $endingPeriod = Carbon::parse("$endingDate $projectB->daily_period_to")->toDateTimeString();

        $result = $this->service->getPeriodByProject($projectB, $startingDate);

        $this->assertEquals($startingPeriod, $result['startingPeriod']);
        $this->assertEquals($endingPeriod, $result['endingPeriod']);
        $this->assertEquals($startingDate, $result['period']);

        /**
         *
         * Project C same day - default date
         * Project C : daily_period_from 00:00:00, daily_period_to 23:59:59 --- Same day
         *
         */
        $startingDate = now()->toDateString();

        $startingPeriod = Carbon::parse("$startingDate $projectC->daily_period_from")->toDateTimeString();
        $endingPeriod = Carbon::parse("$startingDate $projectC->daily_period_to")->toDateTimeString();

        $result = $this->service->getPeriodByProject($projectC, null);

        $this->assertEquals($startingPeriod, $result['startingPeriod']);
        $this->assertEquals($endingPeriod, $result['endingPeriod']);
        $this->assertEquals($startingDate, $result['period']);

        /**
         *
         * Project D same day - chosen date
         * Project D : daily_period_from 08:00:00, daily_period_to 18:59:59 --- Same day
         *
         */
        $startingDate = '2024-02-01';

        $startingPeriod = Carbon::parse("$startingDate $projectD->daily_period_from")->toDateTimeString();
        $endingPeriod = Carbon::parse("$startingDate $projectD->daily_period_to")->toDateTimeString();

        $result = $this->service->getPeriodByProject($projectD, $startingDate);

        $this->assertEquals($startingPeriod, $result['startingPeriod']);
        $this->assertEquals($endingPeriod, $result['endingPeriod']);
        $this->assertEquals($startingDate, $result['period']);
    }

    public function testGetPeriodByProject_v2_chosen_date_before_and_after_eight_am()
    {
        $this->truncate();
        /**
         *
         * projectSevenAm Cross day - chosen date
         * projectSevenAm : daily_period_from 07:00:00, daily_period_to 06:59:59 --- Cross day chosen day
         *
         * 7 oclock  19 Feb 7am - 20 Feb 6.59am MYT ------ 18 Feb 2300 - 19 Feb 2259 UTC
         *
         */

        $mytPeriodFrom = '07:00:00';
        $mytPeriodTo = '06:59:59';

        $utcPeriodFrom = Carbon::parse($mytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $utcPeriodTo = Carbon::parse($mytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectSevenAm = MasterProject::factory()->create([
            'name' => 'Project 7am',
            'daily_period_from' => $utcPeriodFrom,
            'daily_period_to' => $utcPeriodTo,
        ]);

        $chosenDate = '2024-02-19';

        $startingPeriodSevenAm = Carbon::parse("2024-02-18 $projectSevenAm->daily_period_from")->toDateTimeString();
        $endingPeriodSevenAm = Carbon::parse("2024-02-19 $projectSevenAm->daily_period_to")->toDateTimeString();

        $result = $this->service->getPeriodByProject($projectSevenAm, $chosenDate);

        $this->assertEquals($startingPeriodSevenAm, $result['startingPeriod']);
        $this->assertEquals($endingPeriodSevenAm, $result['endingPeriod']);
        $this->assertEquals($chosenDate, $result['period']);

        /**
         *
         * Project eightAm Cross day - chosen date
         * Project eightAm : daily_period_from 08:00:00, daily_period_to 07:59:59 --- Cross day chosen day
         *
         * 8 oclock  19 Feb 8am - 20 Feb 7.59am MYT ------ 19 Feb 0000 - 19 Feb 2359 UTC
         *
         */

         $mytPeriodFrom = '08:00:00';
         $mytPeriodTo = '07:59:59';

         $utcPeriodFrom = Carbon::parse($mytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
         $utcPeriodTo = Carbon::parse($mytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

         $projectEightAm = MasterProject::factory()->create([
             'name' => 'Project 8am',
             'daily_period_from' => $utcPeriodFrom,
             'daily_period_to' => $utcPeriodTo,
         ]);

         $chosenDate = '2024-02-19';

         $startingPeriodEightAm = Carbon::parse("2024-02-19 $projectEightAm->daily_period_from")->toDateTimeString();
         $endingPeriodEightAm = Carbon::parse("2024-02-19 $projectEightAm->daily_period_to")->toDateTimeString();

         $result = $this->service->getPeriodByProject($projectEightAm, $chosenDate);

         $this->assertEquals($startingPeriodEightAm, $result['startingPeriod']);
         $this->assertEquals($endingPeriodEightAm, $result['endingPeriod']);
         $this->assertEquals($chosenDate, $result['period']);

        /**
         *
         * Project NineAm Cross day - chosen date
         * Project NineAm : daily_period_from 08:00:00, daily_period_to 07:59:59 --- Cross day chosen day
         *
         * 9 oclock  19 Feb 9am - 20 Feb 8.59am MYT ------ 19 Feb 0100 - 20 Feb 0059 UTC
         *
         */

         $mytPeriodFrom = '09:00:00';
         $mytPeriodTo = '08:59:59';

         $utcPeriodFrom = Carbon::parse($mytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
         $utcPeriodTo = Carbon::parse($mytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

         $projectNineAm = MasterProject::factory()->create([
             'name' => 'Project 9am',
             'daily_period_from' => $utcPeriodFrom,
             'daily_period_to' => $utcPeriodTo,
         ]);

         $chosenDate = '2024-02-19';

         $startingPeriodNineAm = Carbon::parse("2024-02-19 $projectNineAm->daily_period_from")->toDateTimeString();
         $endingPeriodAm = Carbon::parse("2024-02-20 $projectNineAm->daily_period_to")->toDateTimeString();

         $result = $this->service->getPeriodByProject($projectNineAm, $chosenDate);

         $this->assertEquals($startingPeriodNineAm, $result['startingPeriod']);
         $this->assertEquals($endingPeriodAm, $result['endingPeriod']);
         $this->assertEquals($chosenDate, $result['period']);

        /**
         *
         * Project TenAm Cross day - chosen date
         * Project TenAm : daily_period_from 08:00:00, daily_period_to 07:59:59 --- Cross day chosen day
         *
         * 10 oclock  19 Feb 10am - 20 Feb 9.59am MYT ------ 19 Feb 0200 - 20 Feb 0159 UTC
         */

         $mytPeriodFrom = '10:00:00';
         $mytPeriodTo = '09:59:59';

         $utcPeriodFrom = Carbon::parse($mytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
         $utcPeriodTo = Carbon::parse($mytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

         $projectTenAm = MasterProject::factory()->create([
             'name' => 'Project 10am',
             'daily_period_from' => $utcPeriodFrom,
             'daily_period_to' => $utcPeriodTo,
         ]);

         $chosenDate = '2024-02-19';

         $startingPeriodTenAm = Carbon::parse("2024-02-19 $projectTenAm->daily_period_from")->toDateTimeString();
         $endingPeriodTenAm = Carbon::parse("2024-02-20 $projectTenAm->daily_period_to")->toDateTimeString();

         $result = $this->service->getPeriodByProject($projectTenAm, $chosenDate);

         $this->assertEquals($startingPeriodTenAm, $result['startingPeriod']);
         $this->assertEquals($endingPeriodTenAm, $result['endingPeriod']);
         $this->assertEquals($chosenDate, $result['period']);
    }

    public function testGetPeriodByProject_v3_chosen_date_before_and_after_eight_am_in_same_day()
    {
        $this->truncate();
        /**
         *
         * projectSevenAm Same day - chosen date
         * projectSevenAm : daily_period_from 07:00:00, daily_period_to 22:59:59
         *
         * 7 oclock  19 Feb 7am - 19 Feb 10pm MYT ------ 18 Feb 2300 - 19 Feb 1459 UTC
         * 8 oclock  19 Feb 8am - 19 Feb 11pm MYT ------ 19 Feb 0000 - 19 Feb 1559 UTC
         *
         */

        $mytPeriodFrom = '07:00:00';
        $mytPeriodTo = '22:59:59';

        $utcPeriodFrom = Carbon::parse($mytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $utcPeriodTo = Carbon::parse($mytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectSevenAm = MasterProject::factory()->create([
            'name' => 'Project 7am',
            'daily_period_from' => $utcPeriodFrom,
            'daily_period_to' => $utcPeriodTo,
        ]);

        $chosenDate = '2024-02-19';

        $startingPeriodSevenAm = Carbon::parse("2024-02-18 $projectSevenAm->daily_period_from")->toDateTimeString();
        $endingPeriodSevenAm = Carbon::parse("2024-02-19 $projectSevenAm->daily_period_to")->toDateTimeString();

        $result = $this->service->getPeriodByProject($projectSevenAm, $chosenDate);

        $this->assertEquals($startingPeriodSevenAm, $result['startingPeriod']);
        $this->assertEquals($endingPeriodSevenAm, $result['endingPeriod']);
        $this->assertEquals($chosenDate, $result['period']);

        /**
         *
         * Project eightAm Same day - chosen date
         * Project eightAm : daily_period_from 08:00:00, daily_period_to 23:59:59
         *
         * 8 oclock  19 Feb 8am - 19 Feb 11pm MYT ------ 19 Feb 0000 - 19 Feb 1559 UTC
         *
         */
         $mytPeriodFrom = '08:00:00';
         $mytPeriodTo = '23:59:59';

         $utcPeriodFrom = Carbon::parse($mytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
         $utcPeriodTo = Carbon::parse($mytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

         $projectEightAm = MasterProject::factory()->create([
             'name' => 'Project 8am',
             'daily_period_from' => $utcPeriodFrom,
             'daily_period_to' => $utcPeriodTo,
         ]);

         $chosenDate = '2024-02-19';

         $startingPeriodEightAm = Carbon::parse("2024-02-19 $projectEightAm->daily_period_from")->toDateTimeString();
         $endingPeriodEightAm = Carbon::parse("2024-02-19 $projectEightAm->daily_period_to")->toDateTimeString();

         $result = $this->service->getPeriodByProject($projectEightAm, $chosenDate);

         $this->assertEquals($startingPeriodEightAm, $result['startingPeriod']);
         $this->assertEquals($endingPeriodEightAm, $result['endingPeriod']);
         $this->assertEquals($chosenDate, $result['period']);
    }

    public function testTransformsToStagingWorkerForSingleReader()
    {
        $this->truncate();
        $tagReadDatetime = now();
        $tagRead = (object) [
            'reader_id' => 1,
            'reader_name' => 'name',
            'tag_read_datetime' => $tagReadDatetime,
            'epc' => 'EPC1',
        ];

        $project = MasterProject::factory()->create();

        $period = '2024-01-01';

        $data = $this->service->transformsToStagingWorkerForSingleReader($tagRead, $project, $period);

        $this->assertEquals(1, $data['reader_1_id']);
        $this->assertEquals('name', $data['reader_1_name']);
        $this->assertEquals(null, $data['reader_2_id']);
        $this->assertEquals(null, $data['reader_2_name']);
        $this->assertEquals('EPC1', $data['epc']);
        $this->assertEquals($project->id, $data['project_id']);
        $this->assertEquals($project->name, $data['project_name']);
        $this->assertEquals($tagReadDatetime, $data['tag_read_datetime']);
        $this->assertEquals(null, $data['direction']);
        $this->assertEquals($period, $data['period']);
    }

    public function test_transforms_to_worker_time_log_for_single_readers()
    {
        $this->truncate();
        /**
         *
         * only first tag read passed in
         *
         */
        $tagReadDatetime = now();
        $period = '2024-01-01';

        $project = MasterProject::factory()->create();

        $firstTimeLog = (object) [
            'reader_1_id' => 1,
            'reader_1_name' => 'name',
            'tag_read_datetime' => $tagReadDatetime,
            'epc' => 'EPC1',
            'project_id' => $project->id,
            'project_name' => $project->name,
            'period' => $period
        ];

        $lastTimeLog = (object) [
            'reader_1_id' => 2,
            'reader_1_name' => 'name2',
            'tag_read_datetime' => $tagReadDatetime->addHours(),
            'epc' => 'EPC1',
            'project_id' => $project->id,
            'project_name' => $project->name,
            'period' => $period
        ];

        $data = $this->service->transformsToWorkerTimeLog($firstTimeLog, null, $firstTimeLog);

        $this->assertEquals(1, $data['reader_1_id']);
        $this->assertEquals('name', $data['reader_1_name']);
        $this->assertEquals(null, $data['reader_2_id']);
        $this->assertEquals(null, $data['reader_2_name']);
        $this->assertEquals('EPC1', $data['epc']);
        $this->assertEquals($project->id, $data['project_id']);
        $this->assertEquals($project->name, $data['project_name']);
        $this->assertEquals($tagReadDatetime, $data['clock_in']);
        $this->assertEquals(null, $data['clock_out']);
        $this->assertEquals($period, $data['period']);
        $this->assertEquals($tagReadDatetime, $data['last_tag_read']);
        $this->assertEquals($project->id, $data['project_id']);
        $this->assertEquals($project->name, $data['project_name']);

        /**
         *
         * first and last tag reads passed in
         *
         */
        $firstTagReadDatetime = now();
        $lastTagReadDatetime = now()->addHour();
        $project = MasterProject::factory()->create();

        $firstTimeLog = (object) [
            'reader_1_id' => 1,
            'reader_1_name' => 'name',
            'tag_read_datetime' => $firstTagReadDatetime,
            'epc' => 'EPC1',
            'project_id' => $project->id,
            'project_name' => $project->name,
            'period' => $period
        ];

        $lastTimeLog = (object) [
            'reader_1_id' => 2,
            'reader_1_name' => 'name2',
            'tag_read_datetime' => $lastTagReadDatetime,
            'epc' => 'EPC1',
            'project_id' => $project->id,
            'project_name' => $project->name,
            'period' => $period
        ];

        $period = '2024-01-01';

        $data = $this->service->transformsToWorkerTimeLog($firstTimeLog, $lastTimeLog, $lastTimeLog);

        $this->assertEquals(1, $data['reader_1_id']);
        $this->assertEquals('name', $data['reader_1_name']);
        $this->assertEquals(2, $data['reader_2_id']);
        $this->assertEquals('name2', $data['reader_2_name']);
        $this->assertEquals('EPC1', $data['epc']);
        $this->assertEquals($project->id, $data['project_id']);
        $this->assertEquals($project->name, $data['project_name']);
        $this->assertEquals($firstTagReadDatetime, $data['clock_in']);
        $this->assertEquals($lastTagReadDatetime, $data['clock_out']);
        $this->assertEquals($period, $data['period']);
        $this->assertEquals($lastTagReadDatetime, $data['last_tag_read']);
    }

    public function test_transforms_to_worker_time_log_for_paired_readers()
    {
        $this->truncate();
        /**
         *
         * only clock in tag read passed in
         *
         */
        $tagReadDatetime = now();
        $period = '2024-01-01';

        $project = MasterProject::factory()->create();

        $firstTimeLog = (object) [
            'reader_1_id' => 1, // reader 1 first
            'reader_1_name' => 'reader_1',
            'reader_2_id' => 2, // reader 2 second
            'reader_2_name' => 'reader_2',
            'tag_read_datetime' => $tagReadDatetime,
            'epc' => 'EPC1',
            'project_id' => $project->id,
            'project_name' => $project->name,
            'period' => $period
        ];

        $data = $this->service->transformsToWorkerTimeLog($firstTimeLog, null, $firstTimeLog);

        $this->assertEquals(2, $data['reader_1_id']);
        $this->assertEquals('reader_2', $data['reader_1_name']);
        $this->assertEquals(null, $data['reader_2_id']);
        $this->assertEquals(null, $data['reader_2_name']);
        $this->assertEquals('EPC1', $data['epc']);
        $this->assertEquals($project->id, $data['project_id']);
        $this->assertEquals($project->name, $data['project_name']);
        $this->assertEquals($tagReadDatetime, $data['clock_in']);
        $this->assertEquals(null, $data['clock_out']);
        $this->assertEquals($period, $data['period']);
        $this->assertEquals($tagReadDatetime, $data['last_tag_read']);
        $this->assertEquals($project->id, $data['project_id']);
        $this->assertEquals($project->name, $data['project_name']);

        /**
         *
         * clock in and clock out tag reads passed in
         *
         */
        $firstTagReadDatetime = now();
        $lastTagReadDatetime = now()->addHour();
        $project = MasterProject::factory()->create();

        $firstTimeLog = (object) [
            'reader_1_id' => 1,
            'reader_1_name' => 'reader_1',
            'reader_2_id' => 2,
            'reader_2_name' => 'reader_2',
            'tag_read_datetime' => $firstTagReadDatetime,
            'epc' => 'EPC1',
            'project_id' => $project->id,
            'project_name' => $project->name,
            'period' => $period
        ];

        $lastTimeLog = (object) [
            'reader_1_id' => 2,
            'reader_1_name' => 'reader_2',
            'reader_2_id' => 1,
            'reader_2_name' => 'reader_1',
            'tag_read_datetime' => $lastTagReadDatetime,
            'epc' => 'EPC1',
            'project_id' => $project->id,
            'project_name' => $project->name,
            'period' => $period
        ];

        $period = '2024-01-01';

        $data = $this->service->transformsToWorkerTimeLog($firstTimeLog, $lastTimeLog, $lastTimeLog);

        $this->assertEquals(2, $data['reader_1_id']);
        $this->assertEquals('reader_2', $data['reader_1_name']);
        $this->assertEquals(1, $data['reader_2_id']);
        $this->assertEquals('reader_1', $data['reader_2_name']);
        $this->assertEquals('EPC1', $data['epc']);
        $this->assertEquals($project->id, $data['project_id']);
        $this->assertEquals($project->name, $data['project_name']);
        $this->assertEquals($firstTagReadDatetime, $data['clock_in']);
        $this->assertEquals($lastTagReadDatetime, $data['clock_out']);
        $this->assertEquals($period, $data['period']);
        $this->assertEquals($lastTagReadDatetime, $data['last_tag_read']);
    }

    private function truncate()
    {
        RfidReaderManagement::truncate();
        MasterProject::truncate();
        MasterLocation::truncate();
        RfidTagRead::truncate();
        StagingWorkerTimeLog::truncate();
        WorkerTimeLog::truncate();
        RfidReaderPairing::truncate();
    }
}
