<?php

use App\Http\Controllers\AdminRegisterController;
use App\Http\Controllers\AgentRegisterController;
use App\Http\Controllers\PlotController;
use App\Http\Controllers\SiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::group([ 'middleware'=>'api', 'prefix' => 'auth'], function () {
    Route::post("/register/agent",[AgentRegisterController::class,'registerAgent']);
    Route::post("/login/agent", [AgentRegisterController::class, 'loginAgent']);
    Route::post("/register/admin",[AdminRegisterController::class,'registerAdmin']);
    Route::post("/login/admin",[AdminRegisterController::class,'loginAdmin']);
});

Route::middleware(['auth:sanctum','role:agent'])->group(function () {
    Route::get('/profile/agent', [AgentRegisterController::class, 'profile']);
    Route::post('/add/profile/agent', [AgentRegisterController::class, 'addProfile']);
    Route::post('/changePassword/agent',[AgentRegisterController::class,'changePassword']);
});

Route::middleware(['auth:sanctum','role:admin'])->group(function () {
    Route::get('/profile/Admin',[AdminRegisterController::class, 'profileAdmin']);
    Route::post('/add/Site',[SiteController::class,'addSite']);
    Route::get('/show/Site',[SiteController::class,'showSite']);
    Route::delete('/remove/Site',[SiteController::class,'removeSite']);
    Route::post('/add/Plot',[PlotController::class,'addPlot']);
    Route::get('/show/Plot',[PlotController::class,'showPlot']);
    Route::delete('/remove/Plot',[PlotController::class,'removePlot']);
});


