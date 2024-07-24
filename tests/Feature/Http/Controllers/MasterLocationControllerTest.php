<?php

namespace Tests\Feature\Controllers;

use App\Models\MasterLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\RfidReaderManagement;
use App\Models\User;
use App\Repositories\MasterLocationRepository;
use Illuminate\Support\Facades\Session;

class MasterLocationControllerTest extends TestCase
{
    use RefreshDatabase;

    private $masterLocationRepository;
    private $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->masterLocationRepository = app()->make(MasterLocationRepository::class);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function testIndex()
    {
        MasterLocation::factory(3)->create();

        $response = $this->get(route('master.location.index'));

        $response->assertStatus(200);
        $response->assertViewIs('master-location.index');
        $response->assertViewHas('masterLocations');
    }

    public function testCreate()
    {
        $response = $this->get(route('master.location.create'));
        $response->assertStatus(200);
        $response->assertViewIs('master-location.create');
    }

    public function testStore()
    {
        $data = [
            'name' => 'Kitchen',
        ];

        $response = $this->post(route('master.location.store'), $data);

        $response->assertRedirect(route('master.location.index'));

        $this->assertDatabaseHas('master_locations', [
            'name' => 'Kitchen',
        ]);
    }

    public function testEdit()
    {
        $location = MasterLocation::factory()->create();

        $response = $this->get(route('master.location.edit', $location->id));

        $response->assertStatus(200);
        $response->assertViewIs('master-location.edit');
        $response->assertViewHas('masterLocation', $location);
    }

    public function testUpdate()
    {
        $location = MasterLocation::factory()->create();

        $data = [
            'name' => 'Updated location',
        ];

        $response = $this->put(route('master.location.update', $location->id), $data);

        $response->assertRedirect(route('master.location.index'));

        $this->assertDatabaseHas('master_locations', [
            'name' => 'Updated location',
        ]);
    }

    public function testDelete_but_location_is_used_in_readers()
    {
        $location = MasterLocation::factory()->create();
        $location2 = MasterLocation::factory()->create();

        RfidReaderManagement::factory()->create([
            'location_1_id' => $location->id,
            'location_2_id' => $location2->id,
        ]);

        $this->delete(route('master.location.destroy', $location->id));

        $errors = Session::get('errors')->getBag('default')->get('error')[0];

        $this->assertEquals('Master location is used in RFID reader management.', $errors);

        $this->assertDatabaseHas('master_locations', [
            'id' => $location->id
        ]);
    }
}
