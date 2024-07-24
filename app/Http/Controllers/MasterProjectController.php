<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterProject\StoreMasterProjectRequest;
use App\Http\Requests\MasterProject\UpdateMasterProjectRequest;
use App\Models\MasterProject;
use App\Repositories\MasterProjectRepository;
use App\Repositories\RfidReaderManagementRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MasterProjectController extends Controller
{
    private MasterProjectRepository $masterProjectRepository;
    private RfidReaderManagementRepository $rfidReaderManagementRepository;

    public function __construct(
        MasterProjectRepository $masterProjectRepository,
        RfidReaderManagementRepository $rfidReaderManagementRepository
    ) {
        $this->masterProjectRepository = $masterProjectRepository;
        $this->rfidReaderManagementRepository = $rfidReaderManagementRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $input = $request->all();

        $masterProjects = $this->masterProjectRepository->getProjects($input, true, true);

        return view('master-project.index', [
            'masterProjects' => $masterProjects
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('master-project.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMasterProjectRequest $request)
    {
        $input = $request->validated();

        $input['daily_period_from'] = Carbon::parse($input['daily_period_from'], 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $input['daily_period_to'] = Carbon::parse($input['daily_period_to'], 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $this->masterProjectRepository->store($input);

        return redirect()
            ->route('master.project.index')
            ->with('flash.banner', 'Project created!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MasterProject $masterProject)
    {
        return view('master-project.edit', [
            'masterProject' => $masterProject
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMasterProjectRequest $request, MasterProject $masterProject)
    {
        $input = $request->validated();

        $input['daily_period_from'] = Carbon::parse($input['daily_period_from'], 'Asia/Kuala_Lumpur')->tz('UTC')->startOfMinute()->toTimeString();
        $input['daily_period_to'] = Carbon::parse($input['daily_period_to'], 'Asia/Kuala_Lumpur')->tz('UTC')->endOfMinute()->toTimeString();

        $this->masterProjectRepository->update($masterProject, $input);

        return redirect()
            ->route('master.project.index')
            ->with('flash.banner', 'Project updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MasterProject $masterProject)
    {
        try {
            if ($this->rfidReaderManagementRepository->getRfidReaderManagements(['project_id' => $masterProject->id], false)) {
                throw new \Exception('Master project is used in RFID reader management.', 422);
            }

            $this->masterProjectRepository->destroy($masterProject);

            return redirect()
                ->route('master.project.index')
                ->with('flash.banner', 'Project deleted!');
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
