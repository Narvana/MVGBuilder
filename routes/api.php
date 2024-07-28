<?php

use App\Http\Controllers\AdminRegisterController;
use App\Http\Controllers\AgentRegisterController;
use App\Http\Controllers\ClientControllerController;
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
   
    Route::post('/update/profile/agent', [AgentRegisterController::class, 'updateProfile']);
   
    Route::post('/changePassword/agent',[AgentRegisterController::class,'changePassword']);
   
    Route::post('/addClient/agent',[ClientControllerController::class,'addClient']);

    Route::post('/updateClient/agent',[ClientControllerController::class,'updateClient']);
   
    Route::get('/level', [AgentRegisterController::class, 'showLevel']);

    Route::get('/down/level', [AgentRegisterController::class, 'showSingleLevel']);

});

Route::middleware(['auth:sanctum','role:admin'])->group(function () {
    Route::get('/profile/Admin',[AdminRegisterController::class, 'profileAdmin']);
    Route::post('/add/Site',[SiteController::class,'addSite']);

    Route::delete('/remove/Site',[SiteController::class,'removeSite']);

    Route::post('/add/Plot',[PlotController::class,'addPlot']);

    Route::delete('/remove/Plot',[PlotController::class,'removePlot']);

    Route::delete('remove/Client',[ClientControllerController::class,'removeClient']);
    // 
    Route::post('/plot/transaction',[PlotController::class,'PlotTransaction']);

    Route::delete('remove/Agent',[AgentRegisterController::class,'removeAgent']);
});

Route::get('/show/Site',[SiteController::class,'showSite']);

Route::get('/show/Plot',[PlotController::class,'showPlot']);

Route::get('/show/Client',[ClientControllerController::class,'showClient']);

Route::get('/show/Plot/Sales',[PlotController::class,'showPlotSales']);

Route::get('/show/Agents',[AgentRegisterController::class,'showAllAgents']);

