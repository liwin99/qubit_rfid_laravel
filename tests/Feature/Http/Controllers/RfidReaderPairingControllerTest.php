<?php

namespace Tests\Feature\Controllers;

use App\Models\MasterLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\MasterProject;
use App\Models\RfidReaderManagement;
use App\Models\RfidReaderPairing;
use App\Models\User;

class RfidReaderPairingControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function testIndex()
    {
        RfidReaderPairing::truncate();
        // No pairing
        $response = $this->get(route('rfid.pairing.index'));
        $response->assertStatus(200);
        $response->assertViewIs('rfid-pairing.index');

        $data = $response->original->getData()['rfidReaderPairings'];

        $this->assertCount(0, $data);

        // only 1 pair
        $project = MasterProject::factory()->create();
        $l1 = MasterLocation::factory()->create();
        $l2 = MasterLocation::factory()->create();
        $l3 = MasterLocation::factory()->create();
        $l4 = MasterLocation::factory()->create();
        $l5 = MasterLocation::factory()->create();
        $l6 = MasterLocation::factory()->create();

        $reader1 = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $l1->id,
            'location_2_id' => $l2->id,
        ]);

        $reader2 = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $l3->id,
            'location_2_id' => $l4->id,
        ]);

        $reader3 = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $l5->id,
            'location_2_id' => $l6->id,
        ]);

        RfidReaderPairing::factory()->create([
            'pair_id' => 1,
            'reader_id' => $reader1->id,
            'reader_position' => 1
        ]);

        RfidReaderPairing::factory()->create([
            'pair_id' => 1,
            'reader_id' => $reader2->id,
            'reader_position' => 2
        ]);

        $response = $this->get(route('rfid.pairing.index'));
        $response->assertStatus(200);
        $response->assertViewIs('rfid-pairing.index');
        $response->assertSee($reader1->name);
        $response->assertSee($reader2->name);
        $response->assertDontSee($reader3->name);
        $data = $response->original->getData()['rfidReaderPairings'];

        $this->assertCount(2, $data);
    }

    public function testStore()
    {
        RfidReaderPairing::truncate();

        $project = MasterProject::factory()->create();
        $l1 = MasterLocation::factory()->create();
        $l2 = MasterLocation::factory()->create();
        $l3 = MasterLocation::factory()->create();
        $l4 = MasterLocation::factory()->create();

        $reader1 = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $l1->id,
            'location_2_id' => $l2->id,
        ]);

        $reader2 = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $l3->id,
            'location_2_id' => $l4->id,
        ]);

        $data = [
            'reader_1_id' => $reader1->id,
            'reader_1_position' => 1,
            'reader_2_id' => $reader2->id,
            'reader_2_position' => 2,
        ];

        $response = $this->post(route('rfid.pairing.store'), $data);
        $response->assertRedirect();

        // expect correct pairing exists
        $this->assertDatabaseHas('rfid_reader_pairings', [
            'pair_id' => 1,
            'reader_id' => $reader1->id,
            'reader_position' => 1,
        ]);

        $this->assertDatabaseHas('rfid_reader_pairings', [
            'pair_id' => 1,
            'reader_id' => $reader2->id,
            'reader_position' => 2,
        ]);
    }

    public function testUpdate()
    {
        RfidReaderPairing::truncate();
        $pairId = 1;

        $project = MasterProject::factory()->create();
        $l1 = MasterLocation::factory()->create();
        $l2 = MasterLocation::factory()->create();
        $l3 = MasterLocation::factory()->create();
        $l4 = MasterLocation::factory()->create();
        $l5 = MasterLocation::factory()->create();
        $l6 = MasterLocation::factory()->create();

        $reader1 = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $l1->id,
            'location_2_id' => $l2->id,
        ]);

        $reader2 = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $l3->id,
            'location_2_id' => $l4->id,
        ]);

        $reader3 = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $l5->id,
            'location_2_id' => $l6->id,
        ]);

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $reader1->id,
            'reader_position' => $reader1->id,
        ]);

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => $reader2->id,
            'reader_position' => $reader2->id,
        ]);

        // update reader1 to reader3 only
        $data = [
            'reader_1_id' => $reader3->id,
            'reader_1_position' => 1,
            'reader_2_id' => $reader2->id,
            'reader_2_position' => 2,
        ];

        $response = $this->put(route('rfid.pairing.update', $pairId), $data);
        $response->assertRedirect();

        // expect correct pairing exists
        $this->assertDatabaseHas('rfid_reader_pairings', [
            'pair_id' => $pairId,
            'reader_id' => $reader3->id,
            'reader_position' => 1,
        ]);

        $this->assertDatabaseHas('rfid_reader_pairings', [
            'pair_id' => $pairId,
            'reader_id' => $reader2->id,
            'reader_position' => 2,
        ]);
    }

    public function testDestroy()
    {
        $pairId = 1;

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => 1,
            'reader_position' => 1,
        ]);

        RfidReaderPairing::factory()->create([
            'pair_id' => $pairId,
            'reader_id' => 2,
            'reader_position' => 2,
        ]);

        $response = $this->delete(route('rfid.pairing.destroy', $pairId));
        $response->assertRedirect();

        $this->assertDatabaseMissing('rfid_reader_pairings', [
            'pair_id' => $pairId,
            'reader_id' => 1,
            'reader_position' => 1,
        ]);

        $this->assertDatabaseMissing('rfid_reader_pairings', [
            'pair_id' => $pairId,
            'reader_id' => 2,
            'reader_position' => 2,
        ]);
    }
}
