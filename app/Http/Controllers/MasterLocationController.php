<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterLocation\StoreMasterLocationRequest;
use App\Http\Requests\MasterLocation\UpdateMasterLocationRequest;
use App\Models\MasterLocation;
use App\Repositories\MasterLocationRepository;
use App\Repositories\RfidReaderManagementRepository;
use Illuminate\Http\Request;

class MasterLocationController extends Controller
{
    private MasterLocationRepository $masterLocationRepository;
    private RfidReaderManagementRepository $rfidReaderManagementRepository;

    public function __construct(
        MasterLocationRepository $masterLocationRepository,
        RfidReaderManagementRepository $rfidReaderManagementRepository
    ) {
        $this->masterLocationRepository = $masterLocationRepository;
        $this->rfidReaderManagementRepository = $rfidReaderManagementRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $input = $request->all();

        $masterLocations = $this->masterLocationRepository->getLocations($input, true, true);

        return view('master-location.index', [
            'masterLocations' => $masterLocations
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('master-location.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMasterLocationRequest $request)
    {
        $input = $request->validated();

        $this->masterLocationRepository->store($input);

        return redirect()
            ->route('master.location.index')
            ->with('flash.banner', 'Location created!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MasterLocation $masterLocation)
    {
        return view('master-location.edit', [
            'masterLocation' => $masterLocation
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMasterLocationRequest $request, MasterLocation $masterLocation)
    {
        $input = $request->validated();

        $this->masterLocationRepository->update($masterLocation, $input);

        return redirect()
            ->route('master.location.index')
            ->with('flash.banner', 'Location updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MasterLocation $masterLocation)
    {
        try {
            if ($this->rfidReaderManagementRepository->getRfidReaderManagements(['exact_location_name' => $masterLocation->name], false)) {
                throw new \Exception('Master location is used in RFID reader management.', 422);
            }

            $this->masterLocationRepository->destroy($masterLocation);

            return redirect()
                ->route('master.location.index')
                ->with('flash.banner', 'Location deleted!');
        } catch (\Throwable $th) {
            return redirect()
                ->back()
                ->withErrors(['error' => $th->getMessage()])
                ->withInput()
                ->with('flash.banner', 'Error: ' . $th->getMessage())
                ->with('flash.bannerStyle', 'danger');
        }

    }
}
