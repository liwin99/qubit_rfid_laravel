<?php

namespace App\Http\Controllers;

use App\Http\Requests\RfidHeartbeatFilterRequest;
use App\Repositories\RfidHeartbeatRepository;

class RfidHeartbeatController extends Controller
{
    private RfidHeartbeatRepository $rfidHeartbeatRepository;

    public function __construct(
        RfidHeartbeatRepository $rfidHeartbeatRepository
    )
    {
        $this->rfidHeartbeatRepository = $rfidHeartbeatRepository;
    }

    public function filter(RfidHeartbeatFilterRequest $request)
    {
        $filters = $request->all();

        try {
            $rfid_heartbeats = $this->rfidHeartbeatRepository->filter($filters);

            return response()->json($rfid_heartbeats);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }

    }
}
