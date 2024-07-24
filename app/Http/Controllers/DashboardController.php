<?php

namespace App\Http\Controllers;

use App\Models\MasterLocation;
use App\Models\MasterProject;
use App\Models\RfidReaderManagement;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $readers = RfidReaderManagement::count();
        $projects = MasterProject::count();
        $locations = MasterLocation::count();

        return view('dashboard', [
            'readers' => $readers,
            'projects' => $projects,
            'locations' => $locations,
        ]);
    }
}
