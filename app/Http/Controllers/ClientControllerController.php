<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\AgentProfile;
use App\Models\ClientController;
use App\Models\Plot_Sale;
use App\Models\Plot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClientControllerController extends Controller
{
    //
    public function addClient(Request $request){
        try {
            DB::beginTransaction();
            //code...
            $validator = Validator::make($request->all(), [
                'client_name' => 'required|string',
                'client_contact' => 'required|string|min:10|max:10',
                'client_address' => 'required|string',
                'client_city' => 'nullable|string',
                'client_state' => 'required|string',
                'plot_id' => 'required|integer',
                'buying_type' => 'required|string|in:CASH,EMI',
                'rangeAmount' => 'required|integer',
                'initial_amount'=> 'required|integer'
            ]);
            
            if ($validator->fails()) {
                $errors = $validator->errors()->all(); // Get all error messages
                $formattedErrors = [];
        
                foreach ($errors as $error) {
                    $formattedErrors[] = $error;
                }
    
                return response()->json([
                    'success' => 0,
                    'error' => $formattedErrors[0]
                ], 422);
            }
            
            $data = $validator->validated();
            $agent = Auth::guard('sanctum')->user();
            
            $plot = Plot::where('id',$data['plot_id'])->first();

            if (!$plot) {
                return response()->json([
                    'success' => 0,
                    'error' => 'Plot not found',
                ], 404);
            }
            
            if($data['buying_type'] === 'CASH' && $data['rangeAmount'] < 9500)
            {
                return response()->json([
                    'success' => 0,
                    'error' => "If Buying Type is CASH, then range amount should not be less than 9500",
                ], 409);
            }
            if($data['buying_type'] === 'EMI' && $data['rangeAmount'] < 11500)
            {
                return response()->json([
                    'success' => 0,
                    'error' => "If Buying Type is EMI, then range amount should not be less than 11500",
                ], 409);
            }

            $client = ClientController::where('client_contact', $data['client_contact'])->first();
            
            if (!$client) {
                $newClient = ClientController::create([
                    'client_name' => $data['client_name'],
                    'client_contact' => $data['client_contact'],
                    'client_address' => $data['client_address'],
                    'client_city' => $data['client_city'] ?? '',
                    'client_state' => $data['client_state']
                ]);
            
                if (!$newClient) {
                    return response()->json([
                        'success' => 0,
                        'error' => 'Client Not Added',
                    ], 500);
                }
            
                $clientId = $newClient->id;
            } else {
                $clientId = $client->id; // Use existing client
            }
            
            $existingPlotSale = Plot_Sale::where('plot_id', $data['plot_id'])
                ->where('client_id', $clientId)
                ->first();
            
            if ($existingPlotSale) {
                return response()->json([
                    'success' => 0,
                    'error' => "Plot is already assigned to {$client->client_name}.",
                ], 409);
            }

            $totalAmount = $data['rangeAmount'] * $plot->plot_area;
            $plot_sale = Plot_Sale::create([
                'plot_id' => $data['plot_id'],
                'client_id' => $clientId,
                'agent_id' => $agent->id,
                'buying_type' => $data['buying_type'],
                'initial_amount'=>$data['initial_amount'],
                'totalAmount' => $totalAmount,
                'plot_value' => 0.00
            ]);
            
            if (!$plot_sale) {
                return response()->json([
                    'success' => 0,
                    'error' => 'Failed to create Plot Sale'
                ], 500);
            }
            
            $updateSuccess = $plot->update([
                'plot_status' => 'PENDING'
            ]);
            
            if (!$updateSuccess) {
                return response()->json([
                    'success' => 0,
                    'error' => 'Failed to update plot status',
                ], 500);
            }
            
            
            DB::commit();

            return response()->json([
                'success' => 1,
                'message' => "Client Registered successfully",
                'client' => $client ? $client : $newClient,
                'plot_sale' => $plot_sale,
                'plot' =>$plot
            ], 201);
        } 
        catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
            'success'=>0, 'error' => 'Internal Server Error' . $th->getMessage()], 500);
        }
    }

    public function showClient(Request $request)
    {
        try {
            //code...            
            $clients=ClientController::get();
            $params=$request->query('id');
            if($clients->isEmpty())
            {
                return response()->json(['success'=>0,'error'=>'No data Found'],404);
            }
            if($params){
                $client = ClientController::find($params);
                if(!$client)
                {
                    return response()->json(['success'=>0,'error'=>"No data Found, in id {$params}"],404);   
                }
                return response()->json(['success'=>1,'client'=>$client],200);
            }
            return response()->json(['success'=>1,'clients'=>$clients],200);
        } catch (\Throwable $th) {
            return response()->json(['success'=>0, 'error' => 'Something went wrong. ' . $th->getMessage()], 500);
        }
    }

    public function updateClient(Request $request)
    {
        try {
            //code...
            $params=$request->query('id');
            $client=ClientController::where('id',$params)->first();
            $validator=Validator::make($request->all(),[
                'client_name'=>  'nullable|string',
                'client_contact'=> 'nullable|string|min:10|max:10',
                'client_address'=>'nullable|string',
                'client_city'=>'nullable|string' ,
                'client_state' => 'nullable|string',
            ]);
 
         if ($validator->fails()) {
             $errors = $validator->errors()->all(); // Get all error messages
             $formattedErrors = [];
     
             foreach ($errors as $error) {
                 $formattedErrors[] = $error;
             }
     
             return response()->json([
                 'success' => 0,
                 'error' => $formattedErrors[0]
             ], 422);
         }

          $data = $validator->validated();
              if($client){
                 $client->update($data);
                 return response()->json([
                     'success'=>1,
                     'message' => 'Client updated successfully',
                     'client' => $client
                 ], 201);
             }
             return response()->json([
                'success'=>0,
                'error' => 'Client Not Found',
            ], 404);
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'success'=>0, 
                    'error' =>'Something went wrong. ' . $th->getMessage()
                ], 500);
        }
    }

    public function removeClient(Request $request){
        try {
            //code...
            $params=$request->query('id');
            $client = ClientController::find($params);
            if(!$client)
            {
                return response()->json(['success'=>0,'error'=>"No data Found, in id {$params}"],404);
            }
            $client->delete();
            return response()->json(['success'=>1,'message'=>'Client Removed'],200);
        }catch (\Throwable $th) {
            return response()->json([
                'success'=>0,
                'details' =>'Internal Server Error. ' . $th->getMessage()], 500);
        }
    }

    public function clientContactInfo(Request $request)
    {
        try {
            //code...
            $params=$request->query('client_contact');
            
            if(!$params)
            {
                return response()->json(['success'=>0,'error'=>"provide contact to search clinet"],404);
            }

            $client=ClientController::where('client_contact',$params)->first();

            if(!$client)
            {
                return response()->json(['success'=>0,'error'=>"provide contact to search client"],404);
            }
            unset($client['created_at']);
            unset($client['updated_at']);
             return response()->json(
                [
                    'success'=>1,
                    'data'=>
                    [
                        $client
                    ]
                ],200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success'=>0,
                'details' =>'Internal Server Error. ' . $th->getMessage()], 500);
        }
    }

}
