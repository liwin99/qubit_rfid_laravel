<?php

namespace App\Http\Controllers;

use App\Http\Requests\RfidTagReadFilterRequest;
use App\Repositories\RfidTagReadRepository;
use App\Http\Requests\GetTagReadLogsFromQTimeRequest;
use Illuminate\Support\Facades\Log;

class RfidTagReadController extends Controller
{
    private RfidTagReadRepository $rfidTagReadRepository;

    public function __construct(
        RfidTagReadRepository $rfidTagReadRepository
    )
    {
        $this->rfidTagReadRepository = $rfidTagReadRepository;
    }

    public function filter(RfidTagReadFilterRequest $request)
    {
        $filters = $request->all();

        try {
            $this->validateFilterRequest($filters);

            $rfid_tag_reads = $this->rfidTagReadRepository->filter($filters);

            return response()->json($rfid_tag_reads);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }

    }


    public function filterTms(RfidTagReadFilterRequest $request)
    {
        $filters = $request->all();

        try {
            $this->validateFilterRequest($filters);

            $rfid_tag_reads = $this->rfidTagReadRepository->filterTms($filters);

            return response()->json($rfid_tag_reads);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }

    }

    private function validateFilterRequest($array)
    {
        if (empty($array)) {
            throw new \Exception('Empty body is not allowed to filter.', 422);
        }

        $allowed_filter = ['tag_read_datetime_from', 'tag_read_datetime_to', 'reader_name', 'epc'];

        $filtered_array = array_filter($array,
            function($key) use ($allowed_filter) { return !in_array($key, $allowed_filter);}, ARRAY_FILTER_USE_KEY);

        $disallowed_field = array_keys($filtered_array);

        if (count($disallowed_field) > 0) {
            $verb = count($disallowed_field) == 1 ? 'is' : 'are';
            throw new \Exception(implode(', ', $disallowed_field) . " {$verb} not allowed.", 422);
        }
    }

    public function getTagReadLogsFromQTime(GetTagReadLogsFromQTimeRequest $request)
    {
        $filters = $request->all();

        try {
            $rfid_tag_reads = $this->rfidTagReadRepository->getTagReadLogsFromQTime($filters);

            return response()->json($rfid_tag_reads);
        } catch (\Throwable $e) {
            Log::error('Error in getTagReadLogsFromQTime: ' . $e->getMessage(), [
                'exception' => $e,
                'filters' => $filters
            ]);

            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }

    }
}
