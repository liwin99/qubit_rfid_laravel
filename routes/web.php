<?php

use App\Http\Controllers\AjaxController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterLocationController;
use App\Http\Controllers\MasterProjectController;
use App\Http\Controllers\RfidReaderManagementController;
use App\Http\Controllers\RfidReaderPairingController;
use App\Http\Controllers\RfidReaderStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('master-project')->group(function () {
        Route::get('/', [MasterProjectController::class, 'index'])->name('master.project.index');
        Route::get('/create', [MasterProjectController::class, 'create'])->name('master.project.create');
        Route::get('/edit/{masterProject}', [MasterProjectController::class, 'edit'])->name('master.project.edit');
        Route::post('/', [MasterProjectController::class, 'store'])->name('master.project.store');
        Route::put('/{masterProject}', [MasterProjectController::class, 'update'])->name('master.project.update');
        Route::delete('/{masterProject}', [MasterProjectController::class, 'destroy'])->name('master.project.destroy');
    });

    Route::prefix('master-location')->group(function () {
        Route::get('/', [MasterLocationController::class, 'index'])->name('master.location.index');
        Route::get('/create', [MasterLocationController::class, 'create'])->name('master.location.create');
        Route::get('/edit/{masterLocation}', [MasterLocationController::class, 'edit'])->name('master.location.edit');
        Route::post('/', [MasterLocationController::class, 'store'])->name('master.location.store');
        Route::put('/{masterLocation}', [MasterLocationController::class, 'update'])->name('master.location.update');
        Route::delete('/{masterLocation}', [MasterLocationController::class, 'destroy'])->name('master.location.destroy');
    });

    Route::prefix('rfid-pairing')->group(function () {
        Route::get('/', [RfidReaderPairingController::class, 'index'])->name('rfid.pairing.index');
        Route::get('/create', [RfidReaderPairingController::class, 'create'])->name('rfid.pairing.create');
        Route::get('/edit/{pairId}', [RfidReaderPairingController::class, 'edit'])->name('rfid.pairing.edit');
        Route::post('/', [RfidReaderPairingController::class, 'store'])->name('rfid.pairing.store');
        Route::put('/{pairId}', [RfidReaderPairingController::class, 'update'])->name('rfid.pairing.update');
        Route::delete('/{pairId}', [RfidReaderPairingController::class, 'destroy'])->name('rfid.pairing.destroy');
    });

    Route::prefix('rfid-management')->group(function () {
        Route::get('/', [RfidReaderManagementController::class, 'index'])->name('rfid.management.index');
        Route::post('/', [RfidReaderManagementController::class, 'store'])->name('rfid.management.store');
        Route::put('/{rfidReaderManagement}', [RfidReaderManagementController::class, 'update'])->name('rfid.management.update');
        Route::delete('/{rfidReaderManagement}', [RfidReaderManagementController::class, 'destroy'])->name('rfid.management.destroy');
    });

    Route::prefix('ajax')->group(function () {
        Route::get('/get-managements', [AjaxController::class, 'indexRfidManagement'])->name('ajax.rfid.management.index');
        Route::get('/get-projects', [AjaxController::class, 'indexProject'])->name('ajax.project.index');
        Route::get('/get-locations', [AjaxController::class, 'indexLocation'])->name('ajax.location.index');
    });

    Route::prefix('rfid-status')->group(function () {
        Route::get('/', [RfidReaderStatusController::class, 'index'])->name('rfid.status.index');
    });
});
