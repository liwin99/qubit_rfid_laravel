<?php

namespace Tests\Unit\Repository;

use App\Models\MasterProject;
use App\Repositories\MasterProjectRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterProjectRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->repository = app()->make(MasterProjectRepository::class);
    }

    public function testGetProjects()
    {
        $sampleProject = MasterProject::factory()->create();

        // Getting projects as a list
        $resultList = $this->repository->getProjects(['name' => $sampleProject->name], true, false);
        $this->assertCount(1, $resultList);
        $this->assertEquals($sampleProject->id, $resultList->first()->id);

        // Getting a project without pagination
        $resultSingle = $this->repository->getProjects(['id' => $sampleProject->id], false, false);
        $this->assertInstanceOf(MasterProject::class, $resultSingle);
        $this->assertEquals($sampleProject->id, $resultSingle->id);
    }

    public function testStore()
    {
        $input = [
            'name' => 'New Project',
            'daily_period_from' => '16:00:00',
            'daily_period_to' => '16:01:00',
        ];
        $result = $this->repository->store($input);

        $this->assertInstanceOf(MasterProject::class, $result);
        $this->assertEquals($input['name'], $result->name);
        $this->assertEquals($input['daily_period_from'], $result->daily_period_from);
        $this->assertEquals($input['daily_period_to'], $result->daily_period_to);
    }

    public function testUpdate()
    {
        $sampleProject = MasterProject::factory()->create();

        $input = ['name' => 'Updated Project'];
        $result = $this->repository->update($sampleProject, $input);

        $this->assertInstanceOf(MasterProject::class, $result);
        $this->assertEquals($input['name'], $result->name);
    }

    public function testDestroy()
    {
        $sampleProject = MasterProject::factory()->create();

        $result = $this->repository->destroy($sampleProject);

        $this->assertTrue($result);
        $this->assertNull(MasterProject::find($sampleProject->id));
    }
}
