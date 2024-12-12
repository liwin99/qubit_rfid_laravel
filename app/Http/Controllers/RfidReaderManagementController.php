<?php

namespace App\Http\Controllers;

use App\Http\Requests\RfidReaderManagement\StoreRfidReaderManagementRequest;
use App\Http\Requests\RfidReaderManagement\UpdateRfidReaderManagementRequest;
use App\Http\Requests\RfidGetReaderRequest;
use App\Models\RfidReaderManagement;
use App\Repositories\RfidReaderManagementRepository;
use App\Repositories\RfidReaderPairingRepository;
use Illuminate\Http\Request;

class RfidReaderManagementController extends Controller
{
    private RfidReaderManagementRepository $rfidReaderManagementRepository;
    private RfidReaderPairingRepository $rfidReaderPairingRepository;

    public function getReader(RfidGetReaderRequest $request){
        $filters = $request->all();
        try{
            $rfid_reader_managements = $this->rfidReaderManagementRepository->filter($filters);
            foreach($rfid_reader_managements as $rfid_reader_management){
                $rfid_reader_management['isOnline'] = $rfid_reader_management->isOnline($rfid_reader_management['heartbeats']);
            }
            return response()->json($rfid_reader_managements);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    public function __construct(
        RfidReaderManagementRepository $rfidReaderManagementRepository,
        RfidReaderPairingRepository $rfidReaderPairingRepository
    ) {
        $this->rfidReaderManagementRepository = $rfidReaderManagementRepository;
        $this->rfidReaderPairingRepository = $rfidReaderPairingRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view('rfid-management.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRfidReaderManagementRequest $request)
    {
        $input = $request->validated();

        $this->rfidReaderManagementRepository->store($input);

        return response()->json();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRfidReaderManagementRequest $request, RfidReaderManagement $rfidReaderManagement)
    {
        $input = $request->validated();

        $this->rfidReaderManagementRepository->update($rfidReaderManagement, $input);

        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RfidReaderManagement $rfidReaderManagement)
    {
        try {
            // prevent deletion if reader is already paired
            if ($this->rfidReaderPairingRepository->getPairings(['reader_id' => $rfidReaderManagement->id], false)) {
                throw new \Exception("Error: Reader {$rfidReaderManagement->name} is used in RFID pairings.", 422);
            }

            $this->rfidReaderManagementRepository->destroy($rfidReaderManagement);

            return response()->json();

        } catch (\Throwable $th) {

            return response()->json([
                'message' => $th->getMessage(),
            ], $th->getCode());
        }
    }
}
