<?php

namespace App\Http\Controllers;

use App\Http\Requests\RfidReaderPairing\StoreRfidReaderPairingRequest;
use App\Http\Requests\RfidReaderPairing\UpdateRfidReaderPairingRequest;
use App\Models\RfidReaderManagement;
use App\Models\RfidReaderPairing;
use App\Repositories\RfidReaderManagementRepository;
use App\Repositories\RfidReaderPairingRepository;
use Illuminate\Http\Request;

class RfidReaderPairingController extends Controller
{
    private RfidReaderPairingRepository $rfidReaderPairingRepository;
    private RfidReaderManagementRepository $rfidReaderManagementRepository;

    public function __construct(
        RfidReaderPairingRepository $rfidReaderPairingRepository,
        RfidReaderManagementRepository $rfidReaderManagementRepository
    ) {
        $this->rfidReaderPairingRepository = $rfidReaderPairingRepository;
        $this->rfidReaderManagementRepository = $rfidReaderManagementRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $input = $request->all();
        $input['relationships'] = ['reader'];
        $input['per_page'] = 40;

        // if filter by name then get the pair
        if (isset($input['name'])) {
            $reader = RfidReaderManagement::where('name', 'LIKE', "%{$input['name']}%")->first();

            if ($reader) {
                $pair = RfidReaderPairing::where('reader_id', $reader->id)->first();

                if ($pair) {
                    $input['pair_id'] = $pair->pair_id;

                    unset($input['name']);
                }
            }
        }

        $rfidReaderPairings = $this->rfidReaderPairingRepository->getPairings($input, true, true);

        return view('rfid-pairing.index', [
            'rfidReaderPairings' => $rfidReaderPairings
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $input['sort_by'] = 'name';
        $input['doesntHavePairings'] = true;

        $readers = $this->rfidReaderManagementRepository->getRfidReaderManagements($input, true);

        return view('rfid-pairing.create', [
            'readers' => $readers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRfidReaderPairingRequest $request)
    {
        $input = $request->validated();

        $pairId = RfidReaderPairing::nextPairId('pair_id');

        $this->rfidReaderPairingRepository->insert($pairId, $input);

        return redirect()
            ->route('rfid.pairing.index')
            ->with('flash.banner', 'Pairing created!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($pairId)
    {
        $input['sort_by'] = 'name';

        $pairings = $this->validatePair($pairId);

        $readers = $this->rfidReaderManagementRepository->getRfidReaderManagements($input, true);

        return view('rfid-pairing.edit', [
            'firstReader' => $pairings->first(),
            'secondReader' => $pairings->last(),
            'readers' => $readers,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRfidReaderPairingRequest $request, $pairId)
    {
        $input = $request->validated();

        $this->validatePair($pairId);

        // delete existing pair data
        $this->rfidReaderPairingRepository->destroy($pairId);

        $this->rfidReaderPairingRepository->insert($pairId, $input);

        return redirect()
            ->route('rfid.pairing.index')
            ->with('flash.banner', 'Pairing updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($pairId)
    {
        // delete existing pair data
        $this->rfidReaderPairingRepository->destroy($pairId);

        return redirect()
            ->route('rfid.pairing.index')
            ->with('flash.banner', 'Pairing deleted!');
    }

    private function validatePair($pairId)
    {
        $pairings = RfidReaderPairing::where('pair_id', $pairId)->get();

        // if pair not found
        if ($pairings->count() !== 2) {
            abort(404);
        }

        return $pairings;
    }
}
