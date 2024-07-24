<?php

namespace Tests\Feature;

use App\Models\MasterLocation;
use App\Models\MasterProject;
use App\Models\RfidReaderManagement;
use App\Models\RfidReaderPairing;
use App\Models\RfidTagRead;
use App\Repositories\RfidTagReadRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Faker\Factory as Faker;

class RfidTagReadRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function testFilterByReaderName()
    {
        $faker = Faker::create();

        RfidTagRead::create([
            'reader_name' => 'TestReader',
            'ip_address' => $faker->ipv4,
            'epc' => 'TestEPC',
            'read_count' => $faker->numberBetween(0, 1000),
            'tag_read_datetime' => Carbon::now(),
            'first_seen_timestamp' => time(),
            'last_seen_timestamp' => time(),
            'unique_hash' => hash('sha256', Carbon::now()->timestamp)
        ]);

        $repository = new RfidTagReadRepository();

        $filters = [
            'reader_name' => 'TestReader',
        ];

        $results = $repository->filter($filters);

        $this->assertCount(1, $results);
    }

    public function testFilterByEpc()
    {
        $faker = Faker::create();

        RfidTagRead::create([
            'reader_name' => 'TestReader',
            'ip_address' => $faker->ipv4,
            'epc' => 'TestEPC',
            'read_count' => $faker->numberBetween(0, 1000),
            'tag_read_datetime' => Carbon::now(),
            'first_seen_timestamp' => time(),
            'last_seen_timestamp' => time(),
            'unique_hash' => hash('sha256', Carbon::now()->timestamp)
        ]);

        $repository = new RfidTagReadRepository();

        $filters = [
            'epc' => 'TestEPC',
        ];

        $results = $repository->filter($filters);

        $this->assertCount(1, $results);
    }

    public function testFilterByTagReadDatetime()
    {
        $faker = Faker::create();

        $time = Carbon::now();

        RfidTagRead::create([
            'reader_name' => 'TestReader',
            'ip_address' => $faker->ipv4,
            'epc' => 'TestEPC',
            'read_count' => $faker->numberBetween(0, 1000),
            'tag_read_datetime' => $time,
            'first_seen_timestamp' => time(),
            'last_seen_timestamp' => time(),
            'unique_hash' => hash('sha256', Carbon::now()->timestamp)
        ]);

        $repository = new RfidTagReadRepository();

        $filters = [
            'tag_read_datetime_from' => $time->subSeconds(60),
            'tag_read_datetime_to' => $time->addSeconds(60)
        ];

        $results = $repository->filter($filters);

        $this->assertCount(1, $results);
    }

    public function testFilterByTagReadDatetimeOutOfRange()
    {
        $faker = Faker::create();

        $time = Carbon::now();

        RfidTagRead::create([
            'reader_name' => 'TestReader',
            'ip_address' => $faker->ipv4,
            'epc' => 'TestEPC',
            'read_count' => $faker->numberBetween(0, 1000),
            'tag_read_datetime' => $time,
            'first_seen_timestamp' => time(),
            'last_seen_timestamp' => time(),
            'unique_hash' => hash('sha256', Carbon::now()->timestamp)
        ]);

        $repository = new RfidTagReadRepository();

        $filters = [
            'tag_read_datetime_from' => $time->addSeconds(60),
            'tag_read_datetime_to' => $time->addSeconds(60)
        ];

        $results = $repository->filter($filters);

        $this->assertCount(0, $results);
    }

    public function testGetSingleReaderTagReads_V1()
    {
        /**
         *
         * 2 EPC in one period walking in and walking out using single reader
         *
         */
        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $project = MasterProject::factory()->create([
            'daily_period_from' => '06:00:00',
            'daily_period_to' => '05:59:00',
        ]);

        $reader = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        Carbon::setTestNow(Carbon::createFromTime(7, 30, 0)); // time is set at 730am

        $Epc1TagReadWalkIn = RfidTagRead::factory()->create([
            'reader_name' => $reader->name,
            'epc' => 'EPC1',
            'tag_read_datetime' => now()->addHour(),
        ]);

        $Epc1TagReadWalkOut = RfidTagRead::factory()->create([
            'reader_name' => $reader->name,
            'epc' => 'EPC1',
            'tag_read_datetime' => now()->addHours(8),
        ]);

        $Epc2TagReadWalkIn = RfidTagRead::factory()->create([
            'reader_name' => $reader->name,
            'epc' => 'EPC2',
            'tag_read_datetime' => now()->addHour(),
        ]);

        $Epc2TagReadWalkOut = RfidTagRead::factory()->create([
            'reader_name' => $reader->name,
            'epc' => 'EPC2',
            'tag_read_datetime' => now()->addHours(8),
        ]);

        $invalidTagRead = RfidTagRead::factory()->create([
            'reader_name' => $reader->name,
            'epc' => 'EPC2',
            'tag_read_datetime' => now()->subDays(2)->addHours(8),
        ]);

        // get startingPeriod and endingPeriod for this project
        $startingDate = now()->toDateString();
        $endingDate = Carbon::parse($startingDate)->addDay()->toDateString();
        $startingPeriod = Carbon::parse("$startingDate $project->daily_period_from")->tz('UTC')->toDateTimeString();
        $endingPeriod = Carbon::parse("$endingDate $project->daily_period_to")->tz('UTC')->toDateTimeString();

        $repository = new RfidTagReadRepository();

        $tagReads = $repository->getSingleReaderTagReads($startingPeriod, $endingPeriod, $project)->keyBy('unique_hash');

        $this->assertEquals(4, $tagReads->count());

        $this->assertArrayHasKey($Epc1TagReadWalkIn->unique_hash, $tagReads);
        $this->assertArrayHasKey($Epc1TagReadWalkOut->unique_hash, $tagReads);
        $this->assertArrayHasKey($Epc2TagReadWalkIn->unique_hash, $tagReads);
        $this->assertArrayHasKey($Epc2TagReadWalkOut->unique_hash, $tagReads);
        $this->assertArrayNotHasKey($invalidTagRead->unique_hash, $tagReads);
    }

    public function testGetSingleReaderTagReads_V2()
    {
        /**
         *
         * 1 EPC in one period walking in and walking out using single reader
         * 1 EPC in same period using period pair reader - walk pass reader 1 position to reader 2 position
         * Expect to only see single reader tagread
         *
         */
        $locationOne = MasterLocation::factory()->create();
        $locationTwo = MasterLocation::factory()->create();

        $project = MasterProject::factory()->create([
            'daily_period_from' => '06:00:00',
            'daily_period_to' => '05:59:00',
        ]);

        $singleReader = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader1 = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $locationOne->id,
            'location_2_id' => $locationTwo->id,
        ]);

        $pairReader2 = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
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

        Carbon::setTestNow(Carbon::createFromTime(7, 30, 0)); // time is set at 730am

        $Epc1TagReadWalkIn = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC1',
            'tag_read_datetime' => now()->addHour(),
        ]);

        $Epc1TagReadWalkOut = RfidTagRead::factory()->create([
            'reader_name' => $singleReader->name,
            'epc' => 'EPC1',
            'tag_read_datetime' => now()->addHours(8),
        ]);

        $pairTagRead1 = RfidTagRead::factory()->create([
            'reader_name' => $pairReader1->name,
            'epc' => 'EPC2',
            'tag_read_datetime' => now()->addHour(),
        ]);

        $pairTagRead2 = RfidTagRead::factory()->create([
            'reader_name' => $pairReader2->name,
            'epc' => 'EPC2',
            'tag_read_datetime' => now()->addHour(),
        ]);

        // get startingPeriod and endingPeriod for this project
        $startingDate = now()->toDateString();
        $endingDate = Carbon::parse($startingDate)->addDay()->toDateString();
        $startingPeriod = Carbon::parse("$startingDate $project->daily_period_from")->tz('UTC')->toDateTimeString();
        $endingPeriod = Carbon::parse("$endingDate $project->daily_period_to")->tz('UTC')->toDateTimeString();

        $repository = new RfidTagReadRepository();

        $tagReads = $repository->getSingleReaderTagReads($startingPeriod, $endingPeriod, $project)->keyBy('unique_hash');

        $this->assertEquals(2, $tagReads->count());

        $this->assertArrayHasKey($Epc1TagReadWalkIn->unique_hash, $tagReads);
        $this->assertArrayHasKey($Epc1TagReadWalkOut->unique_hash, $tagReads);
        $this->assertArrayNotHasKey($pairTagRead1->unique_hash, $tagReads);
        $this->assertArrayNotHasKey($pairTagRead2->unique_hash, $tagReads);
    }
}
