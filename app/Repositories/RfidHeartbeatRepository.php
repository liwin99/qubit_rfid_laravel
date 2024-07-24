<?php

namespace App\Repositories;

use App\Models\RfidHeartbeat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RfidHeartbeatRepository
{
    public function filter($filters)
    {
        $query = RfidHeartbeat::query();

        if (isset($filters['reader_name'])) {
            $query->where('reader_name', $filters['reader_name']);
        }

        if (isset($filters['heartbeat_datetime_from'])) {
            $query->where('heartbeat_datetime', '>=', $filters['heartbeat_datetime_from']);
        }

        if (isset($filters['heartbeat_datetime_to'])) {
            $query->where('heartbeat_datetime', '<=', $filters['heartbeat_datetime_to']);
        }

        return $query->get();
    }

    public function insertHeartBeat($data)
    {
        try {
            $readerName = $data['reader_name'];
            $parts = explode('/', $readerName);
            $ipAddress = '';

            if (count($parts) === 2) {
                $readerName = $parts[0];
                $ipAddress = $parts[1];
            }

            $heartbeat['reader_name'] = $readerName;
            $heartbeat['ip_address'] = $ipAddress;
            $heartbeat['heartbeat_sequence_number'] = $data['event_data'];
            $heartbeat['heartbeat_datetime'] = Carbon::now();
            $heartbeat['created_at'] = Carbon::now();

            RfidHeartbeat::insert($heartbeat);
            $messages['data'] = ['Item inserted successfully'];
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            $messages['data']['result'] = ['Failed to insert.' . $th->getMessage()];
        }

        return $messages;
    }
}
