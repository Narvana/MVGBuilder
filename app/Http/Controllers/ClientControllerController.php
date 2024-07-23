<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AgentProfile;
use Illuminate\Http\Request;
use App\Models\ClientController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ClientControllerController extends Controller
{
    //
    public function addClient(Request $request){
        try {
            //code...
            $params=$request->query('id');
            $client=ClientController::where('id',$params)->first();
            $validator=Validator::make($request->all(),[
                'client_name'=> $client ? 'nullable|string' : 'required|string',
                'client_contact'=>$client ? 'nullable|string|min:10|max:10' : 'required|string|min:10|max:10',
                'client_address'=>$client ? 'nullable|string' : 'required|string',
                'client_city'=>'nullable|string' ,
                'client_state' => $client ? 'nullable|string' : 'required|string',
                'plot_id' => $client ? 'nullable|integer' : 'required|integer',
                // 'agent_id' => $client ? 'nullable|integer' : 'required|integer'
            ]);
    
            if ($validator->fails()) {
                $errors = $validator->errors()->all(); // Get all error messages
                $formattedErrors = [];
        
                foreach ($errors as $error) {
                    $formattedErrors[] = $error;
                }
        
                return response()->json([
                    'success' => 0,
                    'errors' => $formattedErrors
                ], 422);
            } 
            
            $data = $validator->validated();

            if($client){
                $client->update($data);
                return response()->json([
                    'success'=>1,
                    'message' => 'Plot updated successfully',
                    'client' => $client
                ], 201);
            }

            $agent = Auth::guard('sanctum')->user();
            $data['agent_id']=$agent->id;
            $newClient=ClientController::create($data);
            return response()->json([
                'success'=>1,
                'message' => 'Plot Added successfully',
                'plot' => $newClient
            ], 201);

        } catch (\Throwable $th) {
            return response()->json(['success'=>0,'message' => 'Something went wrong', 'details' => $th->getMessage()], 500);
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
                return response()->json(['success'=>0,'message'=>'No data Found'],404);
            }
            if($params){
                $client = ClientController::find($params);
                if(!$client)
                {
                    return response()->json(['success'=>0,'message'=>"No data Found, in id {$params}"],404);   
                }
                return response()->json(['success'=>1,'client'=>$client],200);
            }
            return response()->json(['success'=>1,'clients'=>$clients],200);
        } catch (\Throwable $th) {
            return response()->json(['success'=>0,'message' => 'Something went wrong', 'details' => $th->getMessage()], 500);
        }
    }

    public function removeClient(Request $request){
        try {
            //code...
            $params=$request->query('id');
            $client = ClientController::find($params);
            if(!$client)
            {
                return response()->json(['success'=>0,'message'=>"No data Found, in id {$params}"],404);
            }
            $client->delete();
            return response()->json(['success'=>1,'message'=>'Client Removed'],200);
        }catch (\Throwable $th) {
            return response()->json(['success'=>0,'message' => 'Something went wrong', 'details' => $th->getMessage()], 500);
        }
    }

}
