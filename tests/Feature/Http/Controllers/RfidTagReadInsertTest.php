<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\MasterLocation;
use App\Models\RfidHeartbeat;
use App\Models\RfidTagRead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\MasterProject;
use App\Models\RfidReaderManagement;
use App\Models\User;

class RfidTagReadInsertTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testInsertTagReadSuccess()
    {
        RfidTagRead::truncate();

        $data = [
            "reader_name" => "silion_reader/192.168.100.100",
            "event_type" => "tag_read",
            "event_data" => [
                [
                    "epc" => "123",
                    "bank_data" => "",
                    "antenna" => 1,
                    "read_count" => 24,
                    "protocol" => 5,
                    "rssi" => -50,
                    "firstseen_timestamp" => 1694514489103,
                    "lastseen_timestamp" => 1694515589102
                ],
                [
                    "epc" => "CD00A0CB1905001000004180",
                    "bank_data" => "",
                    "antenna" => 1,
                    "read_count" => 54,
                    "protocol" => 5,
                    "rssi" => -2,
                    "firstseen_timestamp" => 169451449910,
                    "lastseen_timestamp" => 1694514499102
                ]
            ]
        ];

        $response = $this->post(url('api/rfid/insert'), $data);
        $response->assertStatus(200);

        $response_content = json_decode($response->getContent(), true);
        $this->assertEquals('0', $response_content['data'][0]['row']);
        $this->assertEquals('Item inserted successfully', $response_content['data'][0]['result']);
        $this->assertEquals('1', $response_content['data'][1]['row']);
        $this->assertEquals('Item inserted successfully', $response_content['data'][1]['result']);
        $this->assertEquals('200', $response_content['code']);
        $this->assertEquals('success', $response_content['status']);

        $this->assertDatabaseHas('rfid_tag_reads', [
            "epc"=> "123",
            "bank_data"=> null,
            "antenna"=> 1,
            "read_count"=> 24,
            "protocol"=> 5,
            "rssi"=> -50,
            "first_seen_timestamp"=> 1694514489103,
            "last_seen_timestamp"=> 1694515589102
        ]);

        $this->assertDatabaseHas('rfid_tag_reads', [
            "epc"=> "CD00A0CB1905001000004180",
            "bank_data"=> null,
            "antenna"=> 1,
            "read_count"=> 54,
            "protocol"=> 5,
            "rssi"=> -2,
            "first_seen_timestamp"=> 169451449910,
            "last_seen_timestamp"=> 1694514499102
        ]);

    }

    public function testInsertTagReadRepeatFail()
    {
        RfidTagRead::truncate();

        $data = [
            "reader_name" => "silion_reader/192.168.100.100",
            "event_type" => "tag_read",
            "event_data" => [
                [
                    "epc" => "123",
                    "bank_data" => "",
                    "antenna" => 1,
                    "read_count" => 24,
                    "protocol" => 5,
                    "rssi" => -50,
                    "firstseen_timestamp" => 1694514489103,
                    "lastseen_timestamp" => 1694515589102
                ]
            ]
        ];

        $response = $this->post(url('api/rfid/insert'), $data);
        $response->assertStatus(200);

        $response_content = json_decode($response->getContent(), true);
        $this->assertEquals('0', $response_content['data'][0]['row']);
        $this->assertEquals('Item inserted successfully', $response_content['data'][0]['result']);

        $this->assertDatabaseHas('rfid_tag_reads', [
            "epc"=> "123",
            "bank_data"=> null,
            "antenna"=> 1,
            "read_count"=> 24,
            "protocol"=> 5,
            "rssi"=> -50,
            "first_seen_timestamp"=> 1694514489103,
            "last_seen_timestamp"=> 1694515589102
        ]);

        $response = $this->post(url('api/rfid/insert'), $data);
        $response->assertStatus(422);

        $response_content = json_decode($response->getContent(), true);
        $this->assertEquals('The tag read was recorded within 10 minutes. Skipped insertion.', $response_content['error']['event_data.0.unique_hash']['0']);

    }

    public function testInsertFailNoEventType()
    {
        RfidTagRead::truncate();

        $data = [
            "reader_name" => "silion_reader/192.168.100.100",
            "event_data" => [
                [
                    "epc" => "123",
                    "bank_data" => "",
                    "antenna" => 1,
                    "read_count" => 24,
                    "protocol" => 5,
                    "rssi" => -50,
                    "firstseen_timestamp" => 1694514489103,
                    "lastseen_timestamp" => 1694515589102
                ],
                [
                    "epc" => "CD00A0CB1905001000004180",
                    "bank_data" => "",
                    "antenna" => 1,
                    "read_count" => 54,
                    "protocol" => 5,
                    "rssi" => -2,
                    "firstseen_timestamp" => 169451449910,
                    "lastseen_timestamp" => 1694514499102
                ]
            ]
        ];

        $response = $this->post(url('api/rfid/insert'), $data);
        $response->assertStatus(422);

        $response_content = json_decode($response->getContent(), true);
        $response_content = json_decode($response->getContent(), true);
        $this->assertEquals('The event type field is required.', $response_content['error']['event_type']['0']);
    }

    public function testInsertHeartBeatSuccess()
    {
        RfidHeartbeat::truncate();

        $data = [
            "reader_name" => "silion_reader/192.168.100.100",
            "event_type" => "heart_beat",
            "event_data" => 1
        ];

        $response = $this->post(url('api/rfid/insert'), $data);
        $response->assertStatus(200);

        $this->assertDatabaseHas('rfid_heartbeats', [
            'heartbeat_sequence_number' => 1
        ]);

        $this->assertDatabaseMissing('rfid_heartbeats', [
            'heartbeat_sequence_number' => 2
        ]);

        $data = [
            "reader_name" => "silion_reader/192.168.100.100",
            "event_type" => "heart_beat",
            "event_data" => 2
        ];

        $response = $this->post(url('api/rfid/insert'), $data);
        $response->assertStatus(200);
        $this->assertDatabaseHas('rfid_heartbeats', [
            'heartbeat_sequence_number' => 2
        ]);
    }


}
