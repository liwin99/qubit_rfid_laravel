<?php

namespace App\Repositories;

use App\Models\RfidTagRead;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RfidLogRepository
{
    public function insertInboundLog($data)
    {
        DB::table('inbound_logs')->insert([
            'payload' => $data['payload'],
            'error_message' => $data['error_message']
        ]);
    }
}
