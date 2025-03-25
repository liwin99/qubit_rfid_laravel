<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Response;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
//
//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::middleware('client')->group(function() {

});

Route::get('/rfid-tag-read/filter', 'App\Http\Controllers\RfidTagReadController@filter')->middleware('auth:api');
Route::get('/rfid-heartbeat/', 'App\Http\Controllers\RfidHeartbeatController@filter')->middleware('auth:api');

Route::get('/rfid/rfid-tag-read-logs/', 'App\Http\Controllers\RfidTagReadController@getTagReadLogsFromQTime')->middleware('auth:api');
Route::post('/rfid/insert', 'App\Http\Controllers\RfidApiController@insert');

Route::get('/rfid/filter', 'App\Http\Controllers\RfidTagReadController@filterTms');
Route::get('/rfid/reader', 'App\Http\Controllers\RfidReaderManagementController@getReader');
