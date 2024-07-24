<?php

namespace Tests\Unit\Repository;

use App\Models\MasterLocation;
use App\Repositories\MasterLocationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterLocationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->repository = app()->make(MasterLocationRepository::class);
    }

    public function testGetLocations()
    {
        $sampleLocation = MasterLocation::factory()->create();

        // Getting locations as a list
        $resultList = $this->repository->getLocations(['name' => $sampleLocation->name], true, false);
        $this->assertCount(1, $resultList);
        $this->assertEquals($sampleLocation->id, $resultList->first()->id);

        // Getting a location without pagination
        $resultSingle = $this->repository->getLocations(['id' => $sampleLocation->id], false, false);
        $this->assertInstanceOf(MasterLocation::class, $resultSingle);
        $this->assertEquals($sampleLocation->id, $resultSingle->id);
    }

    public function testStore()
    {
        $input = ['name' => 'New Location'];
        $result = $this->repository->store($input);

        $this->assertInstanceOf(MasterLocation::class, $result);
        $this->assertEquals($input['name'], $result->name);
    }

    public function testUpdate()
    {
        $sampleLocation = MasterLocation::factory()->create();

        $input = ['name' => 'Updated Location'];
        $result = $this->repository->update($sampleLocation, $input);

        $this->assertInstanceOf(MasterLocation::class, $result);
        $this->assertEquals($input['name'], $result->name);
    }

    public function testDestroy()
    {
        $sampleLocation = MasterLocation::factory()->create();

        $result = $this->repository->destroy($sampleLocation);

        $this->assertTrue($result);
        $this->assertNull(MasterLocation::find($sampleLocation->id));
    }
}
