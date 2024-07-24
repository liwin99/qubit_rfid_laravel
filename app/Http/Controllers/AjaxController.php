<?php

namespace App\Http\Controllers;

use App\Repositories\MasterLocationRepository;
use App\Repositories\MasterProjectRepository;
use App\Repositories\RfidReaderManagementRepository;
use Illuminate\Http\Request;

class AjaxController extends Controller
{
    private RfidReaderManagementRepository $rfidReaderManagementRepository;
    private MasterProjectRepository $masterProjectRepository;
    private MasterLocationRepository $masterLocationRepository;

    public function __construct(
        RfidReaderManagementRepository $rfidReaderManagementRepository,
        MasterProjectRepository $masterProjectRepository,
        MasterLocationRepository $masterLocationRepository,
    ) {
        $this->rfidReaderManagementRepository = $rfidReaderManagementRepository;
        $this->masterProjectRepository = $masterProjectRepository;
        $this->masterLocationRepository = $masterLocationRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function indexRfidManagement(Request $request)
    {
        $input = $request->all();
        $input['relationships'] = ['project', 'locationOne', 'locationTwo', 'locationThree', 'locationFour'];

        $data = $this->rfidReaderManagementRepository->getRfidReaderManagements($input, true, true);

        return $data;
    }

    /**
     * Display a listing of the resource.
     */
    public function indexProject(Request $request)
    {
        $input = $request->all();

        $input['sort_by'] = 'name';

        $data = $this->masterProjectRepository->getProjects($input, true);

        return $data;
    }

    /**
     * Display a listing of the resource.
     */
    public function indexLocation(Request $request)
    {
        $input = $request->all();

        $input['sort_by'] = 'name';

        $data = $this->masterLocationRepository->getLocations($input, true);

        return $data;
    }
}
