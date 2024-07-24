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
use Faker\Factory as Faker;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TmsTest extends TestCase
{
    use RefreshDatabase;

    protected WorkerTimeLogService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = app()->make(WorkerTimeLogService::class);

        Schema::connection('tms_mysql')->dropIfExists('staff');
        Schema::connection('tms_mysql')->create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('rfid')->nullable();
            $table->string('location')->nullable();
            $table->string('incharge')->nullable();
        });

    }

    public function test_get_summarized_worker_time_logs()
    {
        $this->truncate();
        $faker = Faker::create();

        $project = MasterProject::factory()->create();
        $date = Carbon::parse('2024-02-01')->setHour(12);

        $location1 = MasterLocation::factory()->create();
        $location2 = MasterLocation::factory()->create();
        $location3 = MasterLocation::factory()->create();
        $location4 = MasterLocation::factory()->create();

        $management = RfidReaderManagement::factory()->create([
            'name' => 'TestReader',
            'project_id' => $project->id,
            'location_1_id' => $location1->id,
            'location_2_id' => $location2->id,
            'location_3_id' => $location3->id,
            'location_4_id' => $location4->id,
        ]);

        $tagRead = RfidTagRead::create([
            'reader_name' => 'TestReader',
            'ip_address' => $faker->ipv4,
            'epc' => 'TestEPC',
            'read_count' => $faker->numberBetween(0, 1000),
            'tag_read_datetime' => $date,
            'first_seen_timestamp' => time(),
            'last_seen_timestamp' => time(),
            'unique_hash' => hash('sha256', Carbon::now()->timestamp)
        ]);

        $this->assertEquals(0, StagingWorkerTimeLog::count());
        $this->assertEquals(0, WorkerTimeLog::count());
        $this->artisan("cron:process-worker-time-log 2024-02-01 --staging");
        $this->assertEquals(1, StagingWorkerTimeLog::count());
        $this->assertEquals(1, WorkerTimeLog::count());

        $summarized = $this->service->getSummarizedTimeLogToTms($project, $date);
        $this->assertCount(0, $summarized);

        DB::connection('tms_mysql')->table('staff')->insert([
            'code' => 'staffcode',
            'name' => 'staffname',
            'rfid' => $tagRead->epc,
            'location' => 'staffdept',
            'incharge' => 'staffincharge'
        ]);

        $this->artisan("cron:process-worker-time-log 2024-02-01 --staging");
        $this->assertEquals(1, StagingWorkerTimeLog::count());
        $this->assertEquals(1, WorkerTimeLog::count());
        $summarized = $this->service->getSummarizedTimeLogToTms($project, $date);
        $this->assertCount(1, $summarized);
        $summarizedData = $summarized->toArray()[0];
        $expepcted_result = [
            'id' => 2,
            'cdate' => '2024-02-01',
            'cin' => $date,
            'cout' => null,
            'rfid_reader_name' => 'TestReader',
            'rfid_project' => $project->name,
            'rfid_location1' => $location1->name,
            'rfid_location2' => $location2->name,
            'rfid_location3' => $location3->name,
            'rfid_location4' => $location4->name,
            'staffcode' => 'staffcode',
            'staffname' => 'staffname',
            'dept' => 'staffdept',
            'incharge' => 'staffincharge'
        ];
        $this->assertEquals($expepcted_result['id'], $summarizedData['id']);
        $this->assertEquals($expepcted_result['cdate'], $summarizedData['cdate']);
        $this->assertEquals($expepcted_result['cin'], $summarizedData['cin']);
        $this->assertEquals($expepcted_result['cout'], $summarizedData['cout']);
        $this->assertEquals($expepcted_result['rfid_reader_name'], $summarizedData['rfid_reader_name']);
        $this->assertEquals($expepcted_result['rfid_project'], $summarizedData['rfid_project']);
        $this->assertEquals($expepcted_result['rfid_location1'], $summarizedData['rfid_location1']);
        $this->assertEquals($expepcted_result['rfid_location2'], $summarizedData['rfid_location2']);
        $this->assertEquals($expepcted_result['rfid_location3'], $summarizedData['rfid_location3']);
        $this->assertEquals($expepcted_result['rfid_location4'], $summarizedData['rfid_location4']);
        $this->assertEquals($expepcted_result['staffcode'], $summarizedData['staffcode']);
        $this->assertEquals($expepcted_result['staffname'], $summarizedData['staffname']);
        $this->assertEquals($expepcted_result['dept'], $summarizedData['dept']);
        $this->assertEquals($expepcted_result['incharge'], $summarizedData['incharge']);


    }

    public function test_get_summarized_worker_time_logs_reader_2_location_3_4_null()
    {
        $this->truncate();
        $faker = Faker::create();

        $project = MasterProject::factory()->create();

        $location1 = MasterLocation::factory()->create();
        $location2 = MasterLocation::factory()->create();
        $location3 = MasterLocation::factory()->create();
        $location4 = MasterLocation::factory()->create();

        $management = RfidReaderManagement::factory()->create([
            'name' => 'TestReader1',
            'project_id' => $project->id,
            'location_1_id' => $location1->id,
            'location_2_id' => $location2->id,
            'location_3_id' => $location3->id,
            'location_4_id' => $location4->id,
        ]);

        $management = RfidReaderManagement::factory()->create([
            'name' => 'TestReader2',
            'project_id' => $project->id,
            'location_1_id' => $location1->id,
            'location_2_id' => $location2->id,
        ]);

        $date = Carbon::parse('2024-02-01')->setHour(12);
        $cin = Carbon::parse('2024-02-01')->setHour(12);
        $cout = Carbon::parse('2024-02-01')->setHour(13);

        $tagRead = RfidTagRead::create([
            'reader_name' => 'TestReader1',
            'ip_address' => $faker->ipv4,
            'epc' => 'TestEPC',
            'read_count' => $faker->numberBetween(0, 1000),
            'tag_read_datetime' => $cin,
            'first_seen_timestamp' => time(),
            'last_seen_timestamp' => time(),
            'unique_hash' => hash('sha256', 1)
        ]);

        $tagRead = RfidTagRead::create([
            'reader_name' => 'TestReader2',
            'ip_address' => $faker->ipv4,
            'epc' => 'TestEPC',
            'read_count' => $faker->numberBetween(0, 1000),
            'tag_read_datetime' => $cout,
            'first_seen_timestamp' => time(),
            'last_seen_timestamp' => time(),
            'unique_hash' => hash('sha256', 2)
        ]);

        DB::connection('tms_mysql')->table('staff')->insert([
            'code' => 'staffcode',
            'name' => 'staffname',
            'rfid' => $tagRead->epc,
            'location' => 'staffdept',
            'incharge' => 'staffincharge'
        ]);

        $this->artisan("cron:process-worker-time-log 2024-02-01 --staging");
        $this->assertEquals(2, StagingWorkerTimeLog::count());
        $this->assertEquals(1, WorkerTimeLog::count());
        $summarized = $this->service->getSummarizedTimeLogToTms($project, $date);
        $this->assertCount(1, $summarized);
        $summarizedData = $summarized->toArray()[0];
        $expepcted_result = [
            'id' => 1,
            'cdate' => '2024-02-01',
            'cin' => $cin,
            'cout' => $cout,
            'rfid_reader_name' => 'TestReader2',
            'rfid_project' => $project->name,
            'rfid_location1' => $location1->name,
            'rfid_location2' => $location2->name,
            'rfid_location3' => '',
            'rfid_location4' => '',
            'staffcode' => 'staffcode',
            'staffname' => 'staffname',
            'dept' => 'staffdept',
            'incharge' => 'staffincharge'
        ];
        $this->assertEquals($expepcted_result['id'], $summarizedData['id']);
        $this->assertEquals($expepcted_result['cdate'], $summarizedData['cdate']);
        $this->assertEquals($expepcted_result['cin'], $summarizedData['cin']);
        $this->assertEquals($expepcted_result['cout'], $summarizedData['cout']);
        $this->assertEquals($expepcted_result['rfid_reader_name'], $summarizedData['rfid_reader_name']);
        $this->assertEquals($expepcted_result['rfid_project'], $summarizedData['rfid_project']);
        $this->assertEquals($expepcted_result['rfid_location1'], $summarizedData['rfid_location1']);
        $this->assertEquals($expepcted_result['rfid_location2'], $summarizedData['rfid_location2']);
        $this->assertEquals($expepcted_result['rfid_location3'], $summarizedData['rfid_location3']);
        $this->assertEquals($expepcted_result['rfid_location4'], $summarizedData['rfid_location4']);
        $this->assertEquals($expepcted_result['staffcode'], $summarizedData['staffcode']);
        $this->assertEquals($expepcted_result['staffname'], $summarizedData['staffname']);
        $this->assertEquals($expepcted_result['dept'], $summarizedData['dept']);
        $this->assertEquals($expepcted_result['incharge'], $summarizedData['incharge']);

    }

    private function truncate()
    {
        RfidReaderManagement::truncate();
        RfidReaderPairing::truncate();
        MasterProject::truncate();
        RfidTagRead::truncate();
        StagingWorkerTimeLog::truncate();
        WorkerTimeLog::truncate();
    }

}
