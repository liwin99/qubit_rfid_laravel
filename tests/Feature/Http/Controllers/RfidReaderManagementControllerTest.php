<?php

namespace Tests\Feature\Controllers;

use App\Models\MasterLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\MasterProject;
use App\Models\RfidReaderManagement;
use App\Models\RfidReaderPairing;
use App\Models\User;

class RfidReaderManagementControllerTest extends TestCase
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
        $response = $this->get(route('rfid.management.index'));
        $response->assertStatus(200);
        $response->assertViewIs('rfid-management.index');
    }

    public function testStore()
    {
        $project = MasterProject::factory()->create();
        $location1 = MasterLocation::factory()->create();
        $location2 = MasterLocation::factory()->create();
        $location3 = MasterLocation::factory()->create();
        $location4 = MasterLocation::factory()->create();

        $data = [
            'name' => 'new-_123879reader',
            'project_id' => $project->id,
            'location_1_id' => $location1->id,
            'location_2_id' => $location2->id,
            'location_3_id' => $location3->id,
            'location_4_id' => $location4->id,
            'used_for_attendance' => false,
        ];

        $response = $this->post(route('rfid.management.store'), $data);
        $response->assertOk();

        $this->assertDatabaseHas('rfid_reader_managements', $data);
    }

    public function testUpdate()
    {
        $project = MasterProject::factory()->create();
        $location = MasterLocation::factory()->create();
        $location2 = MasterLocation::factory()->create();

        $rfid = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $location->id,
            'used_for_attendance' => false,
        ]);

        // update project only
        $newProject = MasterProject::factory()->create();

        $data = [
            'name' => $rfid->name,
            'project_id' => $newProject->id,
            'location_1_id' => $location->id,
            'location_2_id' => $location2->id,
            'used_for_attendance' => true,
        ];

        $response = $this->put(route('rfid.management.update', $rfid->id), $data);
        $response->assertOk();

        $this->assertDatabaseHas('rfid_reader_managements', [
            'id' => $rfid->id,
            'name' => $rfid->name,
            'project_id' => $newProject->id,
            'location_1_id' => $location->id,
            'location_2_id' => $location2->id,
            'used_for_attendance' => true,
        ]);
    }

    public function testDestroy()
    {
        $project = MasterProject::factory()->create();
        $location = MasterLocation::factory()->create();

        $rfid = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $location->id,
        ]);

        $response = $this->delete(route('rfid.management.destroy', $rfid->id));
        $response->assertOk();

        $this->assertDatabaseMissing('rfid_reader_managements', [
            'id' => $rfid->id
        ]);
    }

    public function testDestroy_fail_because_reader_is_in_pairing()
    {
        $project = MasterProject::factory()->create();
        $location = MasterLocation::factory()->create();

        $rfid = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $location->id,
        ]);

        RfidReaderPairing::factory()->create([
            'pair_id' => 222,
            'reader_id' => $rfid->id
        ]);

        $response = $this->delete(route('rfid.management.destroy', $rfid->id));
        $response->assertUnprocessable();

        $this->assertDatabaseHas('rfid_reader_managements', [
            'id' => $rfid->id
        ]);
    }
}
