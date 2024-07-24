<?php

namespace Tests\Feature\Controllers;

use App\Models\MasterLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\MasterProject;
use App\Models\RfidReaderManagement;
use App\Models\User;

class AjaxControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function testIndexRfidManagement()
    {
        RfidReaderManagement::truncate();
        MasterProject::truncate();
        // No reader
        $response = $this->get(route('ajax.rfid.management.index'));
        $response->assertStatus(200);

        $jsonResponse = $response->json();

        $this->assertCount(0, $jsonResponse['data']);

        // Have 2 reader
        RfidReaderManagement::factory(2)->create();

        $response = $this->get(route('ajax.rfid.management.index'));
        $response->assertStatus(200);

        $jsonResponse = $response->json();

        $this->assertCount(2, $jsonResponse['data']);

        // Filter by name
        $rfid = RfidReaderManagement::factory()->create();

        $response = $this->get(route('ajax.rfid.management.index', ['name' => $rfid->name]));
        $response->assertStatus(200);

        $jsonResponse = $response->json();

        $this->assertCount(1, $jsonResponse['data']);

        // Filter by same project
        $project = MasterProject::factory()->create(['id' => 2]);

        RfidReaderManagement::factory(2)->create([
            'project_id' => $project->id,
        ]);

        $response = $this->get(route('ajax.rfid.management.index', ['project_name' => $project->name]));
        $response->assertStatus(200);

        $jsonResponse = $response->json();

        $this->assertCount(2, $jsonResponse['data']);

        $this->assertDatabaseCount('rfid_reader_managements', 5);
    }

    public function testIndexProject()
    {
        MasterProject::truncate();
        // No project
        $response = $this->get(route('ajax.project.index'));
        $response->assertStatus(200);

        $jsonResponse = $response->json();

        $this->assertCount(0, $jsonResponse);

        // Have 2 project
        MasterProject::factory(2)->create();

        $response = $this->get(route('ajax.project.index'));
        $response->assertStatus(200);

        $jsonResponse = $response->json();

        $this->assertCount(2, $jsonResponse);
    }

    public function testIndexLocation()
    {
        MasterLocation::truncate();
        // No location
        $response = $this->get(route('ajax.location.index'));
        $response->assertStatus(200);

        $jsonResponse = $response->json();

        $this->assertCount(0, $jsonResponse);

        // Have 2 location
        MasterLocation::factory(2)->create();

        $response = $this->get(route('ajax.location.index'));
        $response->assertStatus(200);

        $jsonResponse = $response->json();

        $this->assertCount(2, $jsonResponse);
    }
}
