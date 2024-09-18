<?php

use App\Http\Controllers\AdminRegisterController;
use App\Http\Controllers\AgentIncomeController;
use App\Http\Controllers\AgentRegisterController;
use App\Http\Controllers\AgentRewardController;
use App\Http\Controllers\ClientControllerController;
use App\Http\Controllers\PlotController;
use App\Http\Controllers\SiteController;
use App\Models\AgentReward;
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
   
    Route::post('/update/profile/agent', [AgentRegisterController::class, 'updateProfile']);
      
    Route::post('/change/Password/agent',[AgentRegisterController::class,'changePassword']);
   
    Route::post('/addClient/agent',[ClientControllerController::class,'addClient']);

    Route::post('/updateClient/agent',[ClientControllerController::class,'updateClient']);
   
    // No use
    Route::get('/level', [AgentRegisterController::class, 'showLevel']);

    Route::get('/show/Site/agent',[SiteController::class,'showSite']);
    
    Route::get('/show/Plot/agent',[PlotController::class,'showPlot']);

    // map
    Route::get('show/map/level', [AgentRegisterController::class, 'showMap']);

    // showAgentDown
    Route::get('/show/down/level', [AgentRegisterController::class, 'showAgentDown']);
    
    // Agent Income
    Route::get('/agent/Income/Distributed', [AgentIncomeController::class, 'agentIncomeDISTRIBUTED']);
    Route::get('/agent/Income/Corpus', [AgentIncomeController::class, 'agentIncomeCORPUS']);
    Route::get('/agent/Income/Third', [AgentIncomeController::class, 'agentIncomeThird']);

    
    Route::get('/agent/Client/Info', [AgentRegisterController::class, 'agentClientInfo']);

    Route::get('/Agent/Sales',[AgentIncomeController::class,'AgentSalesYearMonth']);

    Route::get('/Agent/DG/Sales',[PlotController::class,'AgentDGsale']);

    Route::get('/Client/Contact/Info',[ClientControllerController::class,'clientContactInfo']);

    Route::post('/Agent/Reward',[AgentRewardController::class,'AgentReward']);
 
    Route::get('/Get/Agent/Reward',[AgentRewardController::class,'GetAgentReward']);
});

Route::middleware(['auth:sanctum','role:admin'])->group(function () {

    Route::get('/profile/Admin',[AdminRegisterController::class, 'profileAdmin']);

    Route::post('/change/password/Admin',[AdminRegisterController::class, 'changePasswordAdmin']);

    Route::post('/add/Site',[SiteController::class,'addSite']);

    Route::delete('/remove/Site',[SiteController::class,'removeSite']);

    Route::post('/add/Plot',[PlotController::class,'addPlot']);

    Route::delete('/remove/Plot',[PlotController::class,'removePlot']);

    Route::get('/show/Site/admin',[SiteController::class,'showSite']);
    
    Route::get('/show/Plot/admin',[PlotController::class,'showPlotAdmin']);

    Route::get('/show/Client/admin',[ClientControllerController::class,'showClient']);

    Route::delete('remove/Client',[ClientControllerController::class,'removeClient']);

    Route::post('/plot/transaction',[PlotController::class,'PlotTransaction']);

    Route::get('/show/Plot/Sales/admin',[PlotController::class,'showPlotSales']);

    Route::delete('remove/Agent',[AgentRegisterController::class,'removeAgent']);

    Route::get('/show/Agents/admin',[AgentRegisterController::class,'showAllAgents']);

    Route::get('/Agent/Income/Admin',[AgentIncomeController::class,'agentIncomeAdmin']);

    Route::get('/Super/Agent/Income/Admin',[AgentIncomeController::class,'superAgentIncomeAdmin']);
    
    Route::put('/Update/Transaction/Admin',[AgentIncomeController::class,'UpdateAgentIncomeTransaction']);

    Route::put('/Update/Transaction/DG/Admin',[AgentIncomeController::class,'UpdateAgentDGTransaction']);
    
    Route::get('/Agent/DG/Admin',[AgentIncomeController::class,'AdminAgentDGSale']);
    
    Route::get('/Admin/Client/Legder',[ClientControllerController::class,'ClientLedgerADMIN']);

    Route::get('/Admin/Client/Invoice',[ClientControllerController::class,'ClientInvoiceADMIN']);

    Route::get('/Admin/Generated/Client/Invoice',[ClientControllerController::class,'GetInvoices']);

    Route::post('/Admin/Generate/Client/Invoice',[ClientControllerController::class,'CreateINVOICE']);

    Route::post('/Add/Client/EMI/INFO',[ClientControllerController::class,'AddClientEMI']);
    
    Route::get('/Get/Client/EMI/INFO',[ClientControllerController::class,'GetClientEMI']);

    Route::get('/Update/Client/EMI/INFO',[ClientControllerController::class,'UpdateEMIDate']);

    Route::get('/Daily/Transactions/Client',[ClientControllerController::class,'DailyTransactionClient']);
    
    Route::get('/Daily/Transactions/Agent',[AgentIncomeController::class,'DailyTransactionAgent']);


});

Route::get('/Client/List',[ClientControllerController::class,'ClientLists']);

Route::get('/show/Site',[SiteController::class,'showSite']);

Route::get('/show/Plot/Area',[SiteController::class,'showPlotArea']);



// Route::get('/show/Plot',[PlotController::class,'showPlot']);

// Route::get('/show/Client',[ClientControllerController::class,'showClient']);

// Route::get('/show/Plot/Sales',[PlotController::class,'showPlotSales']);

// Route::get('/show/Agents',[AgentRegisterController::class,'showAllAgents']);

// Forgot Password
Route::post('/forgot/Password/agent', [AgentRegisterController::class, 'forgotPassword']);

Route::get('/hello', [ClientControllerController::class, 'Hello']);