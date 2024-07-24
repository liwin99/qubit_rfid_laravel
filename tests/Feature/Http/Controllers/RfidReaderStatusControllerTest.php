<?php

namespace Tests\Feature\Controllers;

use App\Models\MasterLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\MasterProject;
use App\Models\RfidReaderManagement;
use App\Models\User;

class RfidReaderStatusControllerTest extends TestCase
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
        RfidReaderManagement::truncate();

        // No readers
        $response = $this->get(route('rfid.status.index'));
        $response->assertStatus(200);
        $response->assertViewIs('rfid-status.index');

        $data = $response->original->getData()['rfidManagements'];

        $this->assertCount(0, $data);

        // 3 readers
        $project = MasterProject::factory()->create();
        $l1 = MasterLocation::factory()->create();
        $l2 = MasterLocation::factory()->create();
        $l3 = MasterLocation::factory()->create();

        $reader1 = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $l1->id,
        ]);

        $reader2 = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $l2->id,
        ]);

        $reader3 = RfidReaderManagement::factory()->create([
            'project_id' => $project->id,
            'location_1_id' => $l3->id,
        ]);

        $response = $this->get(route('rfid.status.index'));
        $response->assertStatus(200);
        $response->assertViewIs('rfid-status.index');
        $response->assertSee($reader1->name);
        $response->assertSee($reader2->name);
        $response->assertSee($reader3->name);
        $response->assertSee($project->name);
        $response->assertSee($l1->name);
        $response->assertSee($l2->name);
        $response->assertSee($l3->name);

        $data = $response->original->getData()['rfidManagements'];

        $this->assertCount(3, $data);
    }
}
