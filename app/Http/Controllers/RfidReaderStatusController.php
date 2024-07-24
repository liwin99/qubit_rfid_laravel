<?php

namespace App\Http\Controllers;

use App\Repositories\RfidReaderManagementRepository;
use Illuminate\Http\Request;

class RfidReaderStatusController extends Controller
{
    private RfidReaderManagementRepository $rfidReaderManagementRepository;

    public function __construct(
        RfidReaderManagementRepository $rfidReaderManagementRepository
    ) {
        $this->rfidReaderManagementRepository = $rfidReaderManagementRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $input = $request->all();

        $input['getReaderWithLatestHeartbeat'] = true;
        $input['per_page'] = 50;

        if (!isset($input['sort_by']) && !isset($input['sort_by'])) {
            $input['sort_by'] = 'project_name';
            $input['sort_direction'] = 'asc';
        }

        $input['relationships'] = ['project', 'locationOne', 'locationTwo', 'locationThree', 'locationFour'];

        $rfidManagements = $this->rfidReaderManagementRepository->getRfidReaderManagements($input, true, true);

        return view('rfid-status.index', [
            'rfidManagements' => $rfidManagements
        ]);
    }
}
