<?php

namespace Tests\Feature\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\MasterProject;
use App\Models\RfidReaderManagement;
use App\Models\User;
use App\Repositories\MasterProjectRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class MasterProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    private $masterProjectRepository;
    private $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->masterProjectRepository = app()->make(MasterProjectRepository::class);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function testIndex()
    {
        MasterProject::factory(3)->create();

        $response = $this->get(route('master.project.index'));

        $response->assertStatus(200);
        $response->assertViewIs('master-project.index');
        $response->assertViewHas('masterProjects');
    }

    public function testCreate()
    {
        $response = $this->get(route('master.project.create'));
        $response->assertStatus(200);
        $response->assertViewIs('master-project.create');
    }

    public function testStore()
    {
        $KLPeriodFrom = '16:00:00';
        $KLPeriodTo = '16:01:59';

        $utcKLPeriodFrom = Carbon::parse($KLPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $utcKLPeriodTo = Carbon::parse($KLPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $data = [
            'name' => 'Test Project',
            'daily_period_from' => $KLPeriodFrom,
            'daily_period_to' => $KLPeriodTo,
        ];

        $response = $this->post(route('master.project.store'), $data);

        $response->assertRedirect(route('master.project.index'));
        $this->assertDatabaseHas('master_projects', [
            'name' => 'Test Project',
            'daily_period_from' => $utcKLPeriodFrom,
            'daily_period_to' => $utcKLPeriodTo,
        ]);
    }

    public function testEdit()
    {
        $project = MasterProject::factory()->create();

        $response = $this->get(route('master.project.edit', $project->id));

        $response->assertStatus(200);
        $response->assertViewIs('master-project.edit');
        $response->assertViewHas('masterProject', $project);
    }

    public function testUpdate()
    {
        $project = MasterProject::factory()->create();

        $KLPeriodFrom = '12:00:00';
        $KLPeriodTo = '12:01:59';

        $utcKLPeriodFrom = Carbon::parse($KLPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $utcKLPeriodTo = Carbon::parse($KLPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $data = [
            'name' => 'Updated Project',
            'daily_period_from' => $KLPeriodFrom, // this is KL tz need to be converted to UTC
            'daily_period_to' => $KLPeriodTo, // this is KL tz need to be converted to UTC
        ];
        $response = $this->put(route('master.project.update', $project->id), $data);

        $response->assertRedirect(route('master.project.index'));
        $this->assertDatabaseHas('master_projects', [
            'name' => 'Updated Project',
            'daily_period_from' => $utcKLPeriodFrom,
            'daily_period_to' => $utcKLPeriodTo,
        ]);
    }

    public function testDelete_but_project_is_used_in_readers()
    {
        $KLPeriodFrom = '12:00:00';
        $KLPeriodTo = '12:01:59';

        $utcKLPeriodFrom = Carbon::parse($KLPeriodFrom, 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $utcKLPeriodTo = Carbon::parse($KLPeriodTo, 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $project = MasterProject::factory()->create([
            'daily_period_from' => $utcKLPeriodFrom, // this is KL tz need to be converted to UTC
            'daily_period_to' => $utcKLPeriodTo, // this is KL tz need to be converted to UTC
        ]);

        RfidReaderManagement::factory()->create([
            'project_id' => $project->id
        ]);

        $this->delete(route('master.project.destroy', $project->id));

        $errors = Session::get('errors')->getBag('default')->get('error')[0];

        $this->assertEquals('Master project is used in RFID reader management.', $errors);

        $this->assertDatabaseHas('master_projects', [
            'id' => $project->id
        ]);
    }
}
