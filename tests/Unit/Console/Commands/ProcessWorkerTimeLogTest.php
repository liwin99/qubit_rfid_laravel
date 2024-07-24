<?php

namespace Tests\Unit\Console\Commands;

use App\Models\MasterLocation;
use App\Models\MasterProject;
use App\Models\RfidReaderManagement;
use App\Models\RfidReaderPairing;
use App\Models\RfidTagRead;
use App\Services\WorkerTimeLogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessWorkerTimeLogTest extends TestCase
{
    use RefreshDatabase;

    protected WorkerTimeLogService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = app()->make(WorkerTimeLogService::class);
    }

    public function testHandle_one_project_default_date_for_single_reader_tag_read_with_cross_day_7AM()
    {
        /**
         *
         * Reference
         *
         * 19 Feb 7am - 20 Feb 6.59am MYT ------ 18 Feb 23:00 - 19 Feb 22:59  UTC -- cross
         *
         * 19 Feb 8am - 20 Feb 7.59am MYT ------ 19 Feb 00:00 - 19 Feb 23:59 UTC -- cross
         *
         * 19 Feb 9am - 20 Feb 8.59am MYT ------ 19 Feb 01:00 - 20 Feb 00:59 UTC -- cross
         *
         *
         *
         * 19 Feb 7am - 19 Feb 9.59pm MYT ------ 18 Feb 23:00 - 19 Feb 13:59 UTC -- same day
         *
         * 19 Feb 8am - 19 Feb 10.59pm MYT ------ 19 Feb 00:00 - 19 Feb 14:59 UTC -- same day
         *
         * 19 Feb 9am - 19 Feb 11.59am MYT ------ 19 Feb 01:00 - 19 Feb 15:59 UTC -- same day
         *
         *
         *
         * Date right now is 2024-02-20 730am (MYT)
         *
         * ===============================
         * Project One - daily_period_from: 7.00 am (MYT) , daily_period_to: 6.59 am (MYT) - cross day
         *
         *
         * 2 epc walkin/out = 8 tagreads
         */

        Carbon::setTestNow(Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')); // time is set at 730am MYT

        $projectOneMytPeriodFrom = '07:00:00';
        $projectOneMytPeriodTo = '06:59:59';

        $projectOneUtcPeriodFrom = Carbon::parse($projectOneMytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $projectOneUtcPeriodTo = Carbon::parse($projectOneMytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectOne = MasterProject::factory()->create([
            'name' => 'Project One',
            'daily_period_from' => $projectOneUtcPeriodFrom,
            'daily_period_to' => $projectOneUtcPeriodTo,
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $epcOneTag1 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcOneTag2 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 12:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcOneTag3 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 14:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcOneTag4 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcOneTagInvalid = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 06:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // this should be previous period and invalid
        ]);

        $epcTwoTag1 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse('2024-02-19 08:35:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcTwoTag2 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse('2024-02-19 13:35:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcTwoTag3 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse('2024-02-19 14:35:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcTwoTag4 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse('2024-02-19 18:35:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        // ACT
        $this->artisan("cron:process-worker-time-log --staging");

        // ASSERT
        $this->assertDatabaseCount('staging_worker_time_logs', 8);

        $this->assertDatabaseCount('worker_time_logs', 2);

        /**
         * EPC_ONE
         */
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 12:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 14:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        // invalid tag read before the period start
        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 06:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        /**
         * EPC_TWO
         */
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse('2024-02-19 08:35:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse('2024-02-19 13:35:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse('2024-02-19 18:35:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);
    }

    public function testHandle_one_project_default_date_for_single_reader_tag_read_with_cross_day_8AM()
    {
        /**
         *
         * Reference
         *
         * 19 Feb 7am - 20 Feb 6.59am MYT ------ 18 Feb 23:00 - 19 Feb 22:59  UTC -- cross
         *
         * 19 Feb 8am - 20 Feb 7.59am MYT ------ 19 Feb 00:00 - 19 Feb 23:59 UTC -- cross
         *
         * 19 Feb 9am - 20 Feb 8.59am MYT ------ 19 Feb 01:00 - 20 Feb 00:59 UTC -- cross
         *
         *
         *
         * 19 Feb 7am - 19 Feb 9.59pm MYT ------ 18 Feb 23:00 - 19 Feb 13:59 UTC -- same day
         *
         * 19 Feb 8am - 19 Feb 10.59pm MYT ------ 19 Feb 00:00 - 19 Feb 14:59 UTC -- same day
         *
         * 19 Feb 9am - 19 Feb 11.59am MYT ------ 19 Feb 01:00 - 19 Feb 15:59 UTC -- same day
         *
         *
         *
         * Date right now is 2024-02-20 830am (MYT)
         *
         * ===============================
         * Project One - daily_period_from: 8.00 am (MYT) , daily_period_to: 7.59 am (MYT) - cross day
         *
         *
         * 1 epc walkin/out = 2 tagreads
         */

        Carbon::setTestNow(Carbon::parse('2024-02-20 08:30:00', 'Asia/Kuala_Lumpur')); // time is set at 830am MYT

        $projectOneMytPeriodFrom = '08:00:00';
        $projectOneMytPeriodTo = '07:59:59';

        $projectOneUtcPeriodFrom = Carbon::parse($projectOneMytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $projectOneUtcPeriodTo = Carbon::parse($projectOneMytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectOne = MasterProject::factory()->create([
            'name' => 'Project One',
            'daily_period_from' => $projectOneUtcPeriodFrom,
            'daily_period_to' => $projectOneUtcPeriodTo,
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $epcOneTagInvalid = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // this will be invalid because previous period
        ]);

        $epcOneTag1 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcOneTag4 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        // ACT
        $this->artisan("cron:process-worker-time-log --staging");

        // ASSERT
        $this->assertDatabaseCount('staging_worker_time_logs', 2);

        $this->assertDatabaseCount('worker_time_logs', 1);

        /**
         * EPC_ONE
         */
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        // invalid tag read before the period start
        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);
    }

    public function testHandle_one_project_default_date_for_single_reader_tag_read_with_same_day_8AM()
    {
        /**
         *
         * Reference
         *
         * 19 Feb 7am - 20 Feb 6.59am MYT ------ 18 Feb 23:00 - 19 Feb 22:59  UTC -- cross
         *
         * 19 Feb 8am - 20 Feb 7.59am MYT ------ 19 Feb 00:00 - 19 Feb 23:59 UTC -- cross
         *
         * 19 Feb 9am - 20 Feb 8.59am MYT ------ 19 Feb 01:00 - 20 Feb 00:59 UTC -- cross
         *
         *
         *
         * 19 Feb 7am - 19 Feb 9.59pm MYT ------ 18 Feb 23:00 - 19 Feb 13:59 UTC -- same day
         *
         * 19 Feb 8am - 19 Feb 10.59pm MYT ------ 19 Feb 00:00 - 19 Feb 14:59 UTC -- same day
         *
         * 19 Feb 9am - 19 Feb 11.59am MYT ------ 19 Feb 01:00 - 19 Feb 15:59 UTC -- same day
         *
         *
         *
         * Date right now is 2024-02-20 1030pm (MYT)
         *
         * ===============================
         * Project One - daily_period_from: 7.00 am (MYT) , daily_period_to: 9.59 pm (MYT) - same day
         *
         *
         * 1 epc walkin/out = 2 tagreads
         */

        Carbon::setTestNow(Carbon::parse('2024-02-20 22:30:00', 'Asia/Kuala_Lumpur')); // time is set at 1030pm MYT

        $projectOneMytPeriodFrom = '07:00:00';
        $projectOneMytPeriodTo = '21:59:59';

        $projectOneUtcPeriodFrom = Carbon::parse($projectOneMytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $projectOneUtcPeriodTo = Carbon::parse($projectOneMytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectOne = MasterProject::factory()->create([
            'name' => 'Project One',
            'daily_period_from' => $projectOneUtcPeriodFrom,
            'daily_period_to' => $projectOneUtcPeriodTo,
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $epcOneTagInvalid = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcOneTag4 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcOneTag4 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 22:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // this will be invalid because next period
        ]);

        // ACT
        $this->artisan("cron:process-worker-time-log --staging");

        // ASSERT
        $this->assertDatabaseCount('staging_worker_time_logs', 2);

        $this->assertDatabaseCount('worker_time_logs', 1);

        /**
         * EPC_ONE
         */
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        // invalid tag read after the period start
        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 22:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        // check final clock-in/out
        $this->assertDatabaseHas('worker_time_logs', [
            'epc' => 'EPC_ONE',
            'clock_in' => Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'clock_out' => Carbon::parse('2024-02-20 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);
    }

    public function testHandle_one_project_default_date_for_double_reader_tag_read_with_same_day_8AM()
    {
        /**
         *
         * Reference
         *
         * 19 Feb 7am - 20 Feb 6.59am MYT ------ 18 Feb 23:00 - 19 Feb 22:59  UTC -- cross
         *
         * 19 Feb 8am - 20 Feb 7.59am MYT ------ 19 Feb 00:00 - 19 Feb 23:59 UTC -- cross
         *
         * 19 Feb 9am - 20 Feb 8.59am MYT ------ 19 Feb 01:00 - 20 Feb 00:59 UTC -- cross
         *
         *
         *
         * 19 Feb 7am - 19 Feb 9.59pm MYT ------ 18 Feb 23:00 - 19 Feb 13:59 UTC -- same day
         *
         * 19 Feb 8am - 19 Feb 10.59pm MYT ------ 19 Feb 00:00 - 19 Feb 14:59 UTC -- same day
         *
         * 19 Feb 9am - 19 Feb 11.59am MYT ------ 19 Feb 01:00 - 19 Feb 15:59 UTC -- same day
         *
         *
         *
         * Date right now is 2024-02-20 1030pm (MYT)
         *
         * ===============================
         * Project One - daily_period_from: 7.00 am (MYT) , daily_period_to: 9.59 pm (MYT) - same day
         *
         *
         * 1 epc walkin/out = 2 tagreads
         */

        Carbon::setTestNow(Carbon::parse('2024-02-20 22:30:00', 'Asia/Kuala_Lumpur')); // time is set at 1030pm MYT

        $projectOneMytPeriodFrom = '07:00:00';
        $projectOneMytPeriodTo = '21:59:59';

        $projectOneUtcPeriodFrom = Carbon::parse($projectOneMytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $projectOneUtcPeriodTo = Carbon::parse($projectOneMytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectOne = MasterProject::factory()->create([
            'name' => 'Project One',
            'daily_period_from' => $projectOneUtcPeriodFrom,
            'daily_period_to' => $projectOneUtcPeriodTo,
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $reader1 = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $reader2 = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader1->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_EXIT]);
        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader2->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_SITE]);


        $epcOneTag11 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk in - pass reader 1
        ]);


        $epcOneTag22 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 07:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk in - pass reader 2
        ]);

        $epcOneTag4 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk out - pass reader 2
        ]);

        $epcOneTag44 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 19:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk out - pass reader 1
        ]);


        // ACT
        $this->artisan("cron:process-worker-time-log --staging");

        // ASSERT
        $this->assertDatabaseCount('staging_worker_time_logs', 2);

        $this->assertDatabaseCount('worker_time_logs', 1);

        /**
         * EPC_ONE
         */
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'direction' => 'IN',
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 19:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'direction' => 'OUT',
        ]);

        // check final clock-in/out
        $this->assertDatabaseHas('worker_time_logs', [
            'epc' => 'EPC_ONE',
            'clock_in' => Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'clock_out' => Carbon::parse('2024-02-20 19:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);
    }

    public function testHandle_one_project_default_date_for_double_reader_tag_read_with_cross_day_8AM()
    {
        /**
         *
         * Reference
         *
         * 19 Feb 7am - 20 Feb 6.59am MYT ------ 18 Feb 23:00 - 19 Feb 22:59  UTC -- cross
         *
         * 19 Feb 8am - 20 Feb 7.59am MYT ------ 19 Feb 00:00 - 19 Feb 23:59 UTC -- cross
         *
         * 19 Feb 9am - 20 Feb 8.59am MYT ------ 19 Feb 01:00 - 20 Feb 00:59 UTC -- cross
         *
         *
         *
         * 19 Feb 7am - 19 Feb 9.59pm MYT ------ 18 Feb 23:00 - 19 Feb 13:59 UTC -- same day
         *
         * 19 Feb 8am - 19 Feb 10.59pm MYT ------ 19 Feb 00:00 - 19 Feb 14:59 UTC -- same day
         *
         * 19 Feb 9am - 19 Feb 11.59am MYT ------ 19 Feb 01:00 - 19 Feb 15:59 UTC -- same day
         *
         *
         *
         * Date right now is 2024-02-20 830am (MYT)
         *
         * ===============================
         * Project One - daily_period_from: 8.00 am (MYT) , daily_period_to: 7.59 am (MYT) - cross day
         *
         *
         * 1 epc walkin/out = 2 tagreads
         */

        Carbon::setTestNow(Carbon::parse('2024-02-20 08:30:00', 'Asia/Kuala_Lumpur')); // time is set at 830am MYT

        $projectOneMytPeriodFrom = '08:00:00';
        $projectOneMytPeriodTo = '07:59:59';

        $projectOneUtcPeriodFrom = Carbon::parse($projectOneMytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $projectOneUtcPeriodTo = Carbon::parse($projectOneMytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectOne = MasterProject::factory()->create([
            'name' => 'Project One',
            'daily_period_from' => $projectOneUtcPeriodFrom,
            'daily_period_to' => $projectOneUtcPeriodTo,
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $reader1 = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $reader2 = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader1->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_EXIT]);
        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader2->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_SITE]);


        $epcOneTag11 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // invalid tag read because out of period
        ]);


        $epcOneTag22 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk in - pass reader 1
        ]);


        $epcOneTag22 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 08:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk in - pass reader 2
        ]);

        $epcOneTag4 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk out - pass reader 2
        ]);

        $epcOneTag44 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 19:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk out - pass reader 1
        ]);


        // ACT
        $this->artisan("cron:process-worker-time-log --staging");

        // ASSERT
        $this->assertDatabaseCount('staging_worker_time_logs', 2);

        $this->assertDatabaseCount('worker_time_logs', 1);

        /**
         * EPC_ONE
         */
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'direction' => 'IN',
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 19:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'direction' => 'OUT',
        ]);

        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // invalid tag read because out of period
        ]);

        // check final clock-in/out
        $this->assertDatabaseHas('worker_time_logs', [
            'epc' => 'EPC_ONE',
            'clock_in' => Carbon::parse('2024-02-19 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'clock_out' => Carbon::parse('2024-02-19 19:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);
    }

    public function testHandle_one_project_default_date_for_double_reader_tag_read_with_same_day_7AM()
    {
        /**
         *
         * Reference
         *
         * 19 Feb 7am - 20 Feb 6.59am MYT ------ 18 Feb 23:00 - 19 Feb 22:59  UTC -- cross
         *
         * 19 Feb 8am - 20 Feb 7.59am MYT ------ 19 Feb 00:00 - 19 Feb 23:59 UTC -- cross
         *
         * 19 Feb 9am - 20 Feb 8.59am MYT ------ 19 Feb 01:00 - 20 Feb 00:59 UTC -- cross
         *
         *
         *
         * 19 Feb 7am - 19 Feb 9.59pm MYT ------ 18 Feb 23:00 - 19 Feb 13:59 UTC -- same day
         *
         * 19 Feb 8am - 19 Feb 10.59pm MYT ------ 19 Feb 00:00 - 19 Feb 14:59 UTC -- same day
         *
         * 19 Feb 9am - 19 Feb 11.59am MYT ------ 19 Feb 01:00 - 19 Feb 15:59 UTC -- same day
         *
         *
         *
         * Date right now is 2024-02-20 1030pm (MYT)
         *
         * ===============================
         * Project One - daily_period_from: 7.00 am (MYT) , daily_period_to: 9.59 pm (MYT) - same day
         *
         *
         * 1 epc walkin/out = 2 tagreads
         */

        Carbon::setTestNow(Carbon::parse('2024-02-20 22:30:00', 'Asia/Kuala_Lumpur')); // time is set at 1030pm MYT

        $projectOneMytPeriodFrom = '07:00:00';
        $projectOneMytPeriodTo = '21:59:59';

        $projectOneUtcPeriodFrom = Carbon::parse($projectOneMytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $projectOneUtcPeriodTo = Carbon::parse($projectOneMytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectOne = MasterProject::factory()->create([
            'name' => 'Project One',
            'daily_period_from' => $projectOneUtcPeriodFrom,
            'daily_period_to' => $projectOneUtcPeriodTo,
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $reader1 = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $reader2 = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader1->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_EXIT]);
        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader2->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_SITE]);

        $epcOneTag22 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk in - pass reader 1
        ]);

        $epcOneTag22 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 08:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk in - pass reader 2
        ]);

        $epcOneTag4 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk out - pass reader 2
        ]);

        $epcOneTag44 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 19:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk out - pass reader 1
        ]);

        $invalidTagRead1 = RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 22:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // invalid out of period
        ]);

        $invalidTagRead2 = RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 22:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // invalid out of period
        ]);

        // ACT
        $this->artisan("cron:process-worker-time-log --staging");

        // ASSERT
        $this->assertDatabaseCount('staging_worker_time_logs', 2);

        $this->assertDatabaseCount('worker_time_logs', 1);

        /**
         * EPC_ONE
         */
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'direction' => 'IN',
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 19:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'direction' => 'OUT',
        ]);

        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 22:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // invalid out of period
        ]);

        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 22:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // invalid out of period
        ]);

        // check final clock-in/out
        $this->assertDatabaseHas('worker_time_logs', [
            'epc' => 'EPC_ONE',
            'clock_in' => Carbon::parse('2024-02-20 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'clock_out' => Carbon::parse('2024-02-20 19:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);
    }

    public function testHandle_one_project_chosen_date_for_single_reader_tag_read_with_cross_day_7AM()
    {
        /**
         * Date is chosen : 20 Feb
         *
         * ===============================
         * Project One - daily_period_from: 7.00 am (MYT) , daily_period_to: 6.59 am (MYT) - cross day
         *
         * 7 oclock  20 Feb 7am - 21 Feb 6.59am MYT ------ 19 Feb 2300 - 20 Feb 2259 UTC
         * 2 epc walkin/out = 8 tagreads
         */
        $chosenDate = '2024-02-20';

        $projectOneMytPeriodFrom = '07:00:00';
        $projectOneMytPeriodTo = '06:59:59';

        $projectOneUtcPeriodFrom = Carbon::parse($projectOneMytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $projectOneUtcPeriodTo = Carbon::parse($projectOneMytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectOne = MasterProject::factory()->create([
            'name' => 'Project One',
            'daily_period_from' => $projectOneUtcPeriodFrom,
            'daily_period_to' => $projectOneUtcPeriodTo,
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $epcOneTag1 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcOneTag2 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 12:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcOneTag3 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 14:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcOneTag4 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcOneTagInvalid = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 06:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // this should be previous period and invalid
        ]);

        $epcTwoTag1 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse('2024-02-20 08:35:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcTwoTag2 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse('2024-02-20 13:35:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcTwoTag3 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse('2024-02-20 14:35:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcTwoTag4 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse('2024-02-20 18:35:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        // ACT
        $this->artisan("cron:process-worker-time-log $chosenDate --staging");

        // ASSERT
        $this->assertDatabaseCount('staging_worker_time_logs', 8);

        $this->assertDatabaseCount('worker_time_logs', 2);

        /**
         * EPC_ONE
         */
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 12:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 14:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        // invalid tag read before the period start
        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 06:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        /**
         * EPC_TWO
         */
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse('2024-02-20 08:35:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse('2024-02-20 13:35:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_TWO',
            'tag_read_datetime' => Carbon::parse('2024-02-20 18:35:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);
    }

    public function testHandle_one_project_chosen_date_for_single_reader_tag_read_with_cross_day_8AM()
    {
        /**
         * Date is chosen : 20 Feb
         *
         * ===============================
         * Project One - daily_period_from: 8.00 am (MYT) , daily_period_to: 7.59 am (MYT) - cross day
         *
         * 7 oclock  20 Feb 8am - 21 Feb 7.59am MYT ------ 20 Feb 0000 - 20 Feb 2359 UTC
         * 1 epc walkin/out = 4 tagreads
         */
        $chosenDate = '2024-02-20';

        $projectOneMytPeriodFrom = '08:00:00';
        $projectOneMytPeriodTo = '07:59:59';

        $projectOneUtcPeriodFrom = Carbon::parse($projectOneMytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $projectOneUtcPeriodTo = Carbon::parse($projectOneMytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectOne = MasterProject::factory()->create([
            'name' => 'Project One',
            'daily_period_from' => $projectOneUtcPeriodFrom,
            'daily_period_to' => $projectOneUtcPeriodTo,
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $epcOneTagOut = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // this should be excluded because previous period
        ]);

        $epcOneTag1 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcOneTag2 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 12:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcOneTag3 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 14:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $epcOneTag4 = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        // ACT
        $this->artisan("cron:process-worker-time-log $chosenDate --staging");

        // ASSERT
        $this->assertDatabaseCount('staging_worker_time_logs', 4);

        $this->assertDatabaseCount('worker_time_logs', 1);

        /**
         * EPC_ONE
         */
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 12:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 14:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);

        // invalid tag read before the period start
        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);
    }

    public function testHandle_one_project_chosen_date_for_double_reader_tag_read_with_cross_day()
    {
        /**
         * Date is chosen : 20 Feb
         *
         * ===============================
         * Project One - daily_period_from: 7.00 am (MYT) , daily_period_to: 6.59 am (MYT) - cross day
         *
         * 7 oclock  20 Feb 7am - 21 Feb 6.59am MYT ------ 19 Feb 2300 - 20 Feb 2259 UTC
         * EPC_ONE walk reader1 then reader 2 -> 4 times -> 8 tag reads
         */
        $chosenDate = '2024-02-20';

        $projectOneMytPeriodFrom = '07:00:00';
        $projectOneMytPeriodTo = '06:59:59';

        $projectOneUtcPeriodFrom = Carbon::parse($projectOneMytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $projectOneUtcPeriodTo = Carbon::parse($projectOneMytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectOne = MasterProject::factory()->create([
            'name' => 'Project One',
            'daily_period_from' => $projectOneUtcPeriodFrom,
            'daily_period_to' => $projectOneUtcPeriodTo,
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $reader1 = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $reader2 = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader1->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_EXIT]);
        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $reader2->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_SITE]);

        RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk 730 am = reader1
        ]);

        RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-20 07:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk 730 am = reader2 - expect this to be the clock in
        ]);

        RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-20 12:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk 1230 pm = reader2
        ]);

        RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-20 12:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk 1230 pm = reader1
        ]);

        RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-20 13:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk 1330 pm = reader1
        ]);

        RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-20 13:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk 1330 pm = reader2
        ]);

        RfidTagRead::factory()->create([
            'reader_name' => $reader2->name,
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-20 18:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk 1830 pm = reader2
        ]);

        RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-20 18:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk 1830 pm = reader1 - expect this to be the clock out
        ]);

        // invalid tag read out of period
        RfidTagRead::factory()->create([
            'reader_name' => $reader1->name,
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-20 23:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk 1130 pm = invalidTagRead
        ]);

        // ACT
        $this->artisan("cron:process-worker-time-log $chosenDate --staging");

        // ASSERT
        $this->assertDatabaseCount('staging_worker_time_logs', 4);

        $this->assertDatabaseCount('worker_time_logs', 1);

        /**
         * EPC_ONE
         */
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'direction' => 'IN'
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-20 12:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'direction' => 'OUT'
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-20 13:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'direction' => 'IN'
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-20 18:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'direction' => 'OUT'
        ]);

        $this->assertDatabaseMissing('staging_worker_time_logs', [
            'epc' => 'EPC_ONE_PAIR',
            'tag_read_datetime' => Carbon::parse('2024-02-20 23:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // invalidTagRead
        ]);

        $this->assertDatabaseHas('worker_time_logs', [
            'epc' => 'EPC_ONE_PAIR',
            'clock_in' => Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // take first walk in thru pair but take last reader
            'clock_out' => Carbon::parse('2024-02-20 18:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // take last walk in thru pair but take last reader
            'period' => $chosenDate,
            'last_tag_read' => Carbon::parse('2024-02-20 18:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);
    }

    public function testHandle_multiple_project_chosen_date_for_single_reader_tag_read_with_cross_day()
    {
        /**
         * Date is chosen : 20 Feb
         *
         * ===============================
         * Project One - daily_period_from: 7.00 am (MYT) , daily_period_to: 6.59 am (MYT) - cross day
         *
         * 7 oclock  20 Feb 7am - 21 Feb 6.59am MYT ------ 19 Feb 2300 - 20 Feb 2259 UTC

         * ===============================
         * Project Two - daily_period_from: 8.00 am (MYT) , daily_period_to: 7.59 am (MYT) - cross day
         *
         * 8 oclock  20 Feb 8am - 21 Feb 7.59am MYT ------ 20 Feb 0000 - 20 Feb 2359 UTC

         * ===============================
         * Project Three - daily_period_from: 9.00 am (MYT) , daily_period_to: 8.59 am (MYT) - cross day
         *
         * 9 oclock  20 Feb 9am - 21 Feb 8.59am MYT ------ 20 Feb 0100 - 21 Feb 0059 UTC
         */

        $chosenDate = '2024-02-20';

        $projectOneMytPeriodFrom = '07:00:00';
        $projectOneMytPeriodTo = '06:59:59';

        $projectOneUtcPeriodFrom = Carbon::parse($projectOneMytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $projectOneUtcPeriodTo = Carbon::parse($projectOneMytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectOne = MasterProject::factory()->create([
            'name' => 'Project One',
            'daily_period_from' => $projectOneUtcPeriodFrom,
            'daily_period_to' => $projectOneUtcPeriodTo,
        ]);

        $this->addSingleTagReadToProject(2, $projectOne, $chosenDate, 3); // insert 2 tag read within period under this project and insert 3 tagreads out of period

        $projectTwoMytPeriodFrom = '08:00:00';
        $projectTwoMytPeriodTo = '07:59:59';

        $projectTwoUtcPeriodFrom = Carbon::parse($projectTwoMytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $projectTwoUtcPeriodTo = Carbon::parse($projectTwoMytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectTwo = MasterProject::factory()->create([
            'name' => 'Project Two',
            'daily_period_from' => $projectTwoUtcPeriodFrom,
            'daily_period_to' => $projectTwoUtcPeriodTo,
        ]);

        $this->addSingleTagReadToProject(4, $projectTwo, $chosenDate, 3); // insert 4 tag read within period under this project and insert 3 tagreads out of period

        $projectThreeMytPeriodFrom = '09:00:00';
        $projectThreeMytPeriodTo = '08:59:59';

        $projectThreeUtcPeriodFrom = Carbon::parse($projectThreeMytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $projectThreeUtcPeriodTo = Carbon::parse($projectThreeMytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectThree = MasterProject::factory()->create([
            'name' => 'Project Three',
            'daily_period_from' => $projectThreeUtcPeriodFrom,
            'daily_period_to' => $projectThreeUtcPeriodTo,
        ]);

        $this->addSingleTagReadToProject(6, $projectThree, $chosenDate, 3); // insert 6 tag read within period under this project and insert 3 tagreads out of period

        $this->artisan("cron:process-worker-time-log $chosenDate --staging");

        $this->assertDatabaseCount('staging_worker_time_logs', 12);

        $this->assertDatabaseCount('worker_time_logs', 3); // 3 count because there is 1 epc for each project
    }


    public function test_handle_one_project_default_date_for_hybrid_reader_with_walking_in_using_single_reader_and_walking_out_using_pair_reader()
    {
        /**
         *
         * Date right now is 2024-02-20 730am (MYT)
         *
         * ===============================
         * Project One - daily_period_from: 7.00 am (MYT) , daily_period_to: 6.59 pm (MYT) - cross day
         *
         *
         * 1 epc walking in using pair reader then get out using single reader
         */

        Carbon::setTestNow(Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')); // time is set at 730am MYT

        $projectOneMytPeriodFrom = '07:00:00';
        $projectOneMytPeriodTo = '06:59:59';

        $projectOneUtcPeriodFrom = Carbon::parse($projectOneMytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $projectOneUtcPeriodTo = Carbon::parse($projectOneMytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectOne = MasterProject::factory()->create([
            'name' => 'Project One',
            'daily_period_from' => $projectOneUtcPeriodFrom,
            'daily_period_to' => $projectOneUtcPeriodTo,
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader1 = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader2 = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $pairReader1->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_EXIT]);
        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $pairReader2->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_SITE]);

        RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk pass single reader at 830am
        ]);

        RfidTagRead::factory()->create([
            'reader_name' => $pairReader2->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk out - pass reader 2 first
        ]);

        RfidTagRead::factory()->create([
            'reader_name' => $pairReader1->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 19:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk out - pass reader 1 second
        ]);


        // ACT
        $this->artisan("cron:process-worker-time-log --staging");

        // ASSERT
        $this->assertDatabaseCount('staging_worker_time_logs', 2);

        $this->assertDatabaseCount('worker_time_logs', 1);

        /**
         * EPC_ONE
         */
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'direction' => null,
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 19:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'direction' => 'OUT',
        ]);

        // check final clock-in/out
        $this->assertDatabaseHas('worker_time_logs', [
            'reader_1_name' => $singleReader->name,
            'reader_2_name' => $pairReader1->name,
            'epc' => 'EPC_ONE',
            'clock_in' => Carbon::parse('2024-02-19 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'clock_out' => Carbon::parse('2024-02-19 19:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);
    }

    public function test_handle_one_project_default_date_for_hybrid_reader_with_walking_in_using_pair_reader_and_walking_out_using_single_reader()
    {
        /**
         *
         * Date right now is 2024-02-20 730am (MYT)
         *
         * ===============================
         * Project One - daily_period_from: 7.00 am (MYT) , daily_period_to: 6.59 pm (MYT) - same day
         *
         *
         * 1 epc walking in using pair reader then get out using single reader
         */

        Carbon::setTestNow(Carbon::parse('2024-02-20 07:30:00', 'Asia/Kuala_Lumpur')); // time is set at 730am MYT

        $projectOneMytPeriodFrom = '07:00:00';
        $projectOneMytPeriodTo = '06:59:59';

        $projectOneUtcPeriodFrom = Carbon::parse($projectOneMytPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $projectOneUtcPeriodTo = Carbon::parse($projectOneMytPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $projectOne = MasterProject::factory()->create([
            'name' => 'Project One',
            'daily_period_from' => $projectOneUtcPeriodFrom,
            'daily_period_to' => $projectOneUtcPeriodTo,
        ]);

        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader1 = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader2 = RfidReaderManagement::factory()->create([
            'project_id' => $projectOne->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $pairReader1->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_EXIT]);
        RfidReaderPairing::create(['pair_id' => 1, 'reader_id' => $pairReader2->id, 'reader_position' => RfidReaderPairing::CLOSE_TO_SITE]);

        RfidTagRead::factory()->create([
            'reader_name' => $pairReader1->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk in - pass reader 1
        ]);

        RfidTagRead::factory()->create([
            'reader_name' => $pairReader2->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 08:30:02', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk in - pass reader 2
        ]);

        RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'), // walk pass single reader at 730am
        ]);

        // ACT
        $this->artisan("cron:process-worker-time-log --staging");

        // ASSERT
        $this->assertDatabaseCount('staging_worker_time_logs', 2);

        $this->assertDatabaseCount('worker_time_logs', 1);

        /**
         * EPC_ONE
         */
        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'direction' => 'IN',
        ]);

        $this->assertDatabaseHas('staging_worker_time_logs', [
            'epc' => 'EPC_ONE',
            'tag_read_datetime' => Carbon::parse('2024-02-19 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'direction' => null,
        ]);

        // check final clock-in/out
        $this->assertDatabaseHas('worker_time_logs', [
            'reader_1_name' => $pairReader2->name,
            'reader_2_name' => $singleReader->name,
            'epc' => 'EPC_ONE',
            'clock_in' => Carbon::parse('2024-02-19 08:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
            'clock_out' => Carbon::parse('2024-02-19 19:30:00', 'Asia/Kuala_Lumpur')->tz('UTC'),
        ]);
    }

    /**
     * Adding 1 epc = tagreadCounts to project within period
     */
    private function addSingleTagReadToProject($tagReadCounts, $project, $chosenDate = null, $outOfPeriodCount = null)
    {
        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $period = $this->service->getPeriodByProject($project, $chosenDate);

        for ($i = 0; $i < $tagReadCounts; $i++) {
            $randomTimeWithinPeriod = Carbon::createFromTimestamp(rand(Carbon::createFromFormat('Y-m-d H:i:s', $period['startingPeriod'])->timestamp, Carbon::createFromFormat('Y-m-d H:i:s', $period['endingPeriod'])->timestamp));

            RfidTagRead::factory()->create([
                'reader_name' => $singleReader->name,
                'epc' => 'EPC_ONE',
                'tag_read_datetime' => $randomTimeWithinPeriod,
            ]);
        }

        if ($outOfPeriodCount !== null) {
            // create tag reads where tag reads are out of current period
            for ($i = 0; $i < $outOfPeriodCount; $i++) {
                $randomTimeBeforePeriod = Carbon::createFromTimestamp(rand(Carbon::createFromFormat('Y-m-d H:i:s', $period['startingPeriod'])->subDays(rand(10, 40))->timestamp, Carbon::createFromFormat('Y-m-d H:i:s', $period['startingPeriod'])->timestamp));

                RfidTagRead::factory()->create([
                    'reader_name' => $singleReader->name,
                    'epc' => 'EPC_ONE',
                    'tag_read_datetime' => $randomTimeBeforePeriod,
                ]);
            }
        }
    }
}
