<?php

use App\Http\Controllers\AgentRegisterController;
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


Route::group(['middleware' => 'api', 'prefix' => 'auth'], function () {
    Route::post("/register/agent",[AgentRegisterController::class,'registerAgent']);
    Route::get('/login/agent', [AgentRegisterController::class, 'loginAgent']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile/agent', [AgentRegisterController::class, 'profile']);
});


