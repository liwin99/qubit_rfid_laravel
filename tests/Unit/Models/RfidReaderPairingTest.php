<?php

namespace Tests\Unit\Models;

use App\Models\RfidReaderPairing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfidReaderPairingTest extends TestCase
{
    use RefreshDatabase;

    public function testNextPairId()
    {
        // no data expect next pairId is 1
        $pairId = RfidReaderPairing::nextPairId();

        $this->assertEquals(1, $pairId);

        // added 1 pairing, expect next PairId is 2
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

        $newPairId = RfidReaderPairing::nextPairId();

        $this->assertEquals(2, $newPairId);
    }
}
