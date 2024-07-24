<?php

namespace Tests\Unit\Models;

use App\Models\RfidReaderManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfidReaderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function testIsOnline()
    {
        $model = app()->make(RfidReaderManagement::class);

        // No heartbeat from DB -> expect isOnline == false
        $data = [];

        $result = $model->isOnline($data);

        $this->assertEquals(false, $result['isOnline']);
        $this->assertEquals('No Hearbeat Recorded', $result['display']);

        // Old heartbeat from DB -> expect isOnline == false
        $passedTime = now()->subMinutes(config('qubit.reader_minutes_offline'));

        $data = collect([
            (object) ['max_heartbeat_datetime' => $passedTime]
        ]);

        $result = $model->isOnline($data);

        $this->assertEquals(false, $result['isOnline']);
        $this->assertEquals($passedTime->tz('Asia/Kuala_Lumpur')->toDayDateTimeString(), $result['display']);

        // New heartbeat from DB -> expect isOnline == true
        $withinTime = now()->subMinutes(config('qubit.reader_minutes_offline'))->addMinute();

        $data = collect([
            (object) ['max_heartbeat_datetime' => $withinTime]
        ]);

        $result = $model->isOnline($data);

        $this->assertEquals(true, $result['isOnline']);
        $this->assertEquals($withinTime->tz('Asia/Kuala_Lumpur')->toDayDateTimeString(), $result['display']);
    }
}
