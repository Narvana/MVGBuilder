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
    /**
     * @group Clients Management
     *
     * Add a new client or Existing one.
     *
     * This endpoint allows you to add a new client or a existing client and assign a plot to that client.
     *
     * @bodyParam client_name string required The name of the client. Example: John Doe
     * @bodyParam client_contact string required The client's contact number (must be 10 digits). Example: 1234567890
     * @bodyParam client_address string required The client's address. Example: 123 Main Street
     * @bodyParam client_city string The city of the client. Example: New York
     * @bodyParam client_state string required The state of the client. Example: NY
     * @bodyParam plot_id integer required The ID of the plot to assign to the client. Example: 1
     * @bodyParam buying_type string required The type of purchase (CASH or EMI). Example: CASH
     * @bodyParam rangeAmount integer required The amount for the purchase. Example: 10000
     * @bodyParam initial_amount integer required The initial payment amount. Example: 2000
     *
     * @response 201 {
     *    "success": 1,
     *    "message": "Client Registered successfully",
     *    "client": {...},
     *    "plot_sale": {...},
     *    "plot": {...}
     * }
     * @response 422 {
     *    "success": 0,
     *    "error": "Validation error message"
     * }
     * @response 404 {
     *    "success": 0,
     *    "error": "Plot not found"
     * }
     */

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
                    'error' => "CASH, Must be 9,500 or above",
                ], 409);
            }
            if($data['buying_type'] === 'EMI' && $data['rangeAmount'] < 11500)
            {
                return response()->json([
                    'success' => 0,
                    'error' => "EMI, Must be 11,500 or above",
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


    public function ClientLedgerADMIN(Request $request)
    {
        $id= $request->query('id');

        $siteID=$request->query('siteID');
        if(!$siteID)
        {
            return response()->json(
                [
                    'success'=>0,
                    'message'=>'Please Select Site Name First'
                ],200);
        }
        $Ledger = DB::table('client_controllers')
        ->leftJoin('plot_sales', 'client_controllers.id', '=', 'plot_sales.client_id')
        ->leftJoin('plots', 'plot_sales.plot_id', '=', 'plots.id')
        ->leftJoin('sites', 'plots.site_id', '=', 'sites.id')
        ->leftJoin('plot_transactions', 'plot_sales.id', '=', 'plot_transactions.plot_sale_id')
        ->select(
            'client_controllers.id',
            'client_controllers.client_name',
            'client_controllers.client_contact',
            'sites.site_name',
            'plots.plot_area',
            'plot_sales.plot_value',
            'plot_transactions.transaction_id',
            'plot_transactions.amount',
            DB::raw('DATE(plot_transactions.created_at) as transaction_date')
        )
        ->where('plot_sales.plot_status', 'BOOKED')
        ->whereIn('sites.id',[$siteID]);

        if($id)
        {
          $Client=$Ledger->where('client_controllers.id',$id)->get();
            if($Client->isEmpty())
            {
                return response()->json(
                [
                    'success'=>0,
                    'message'=> "This client don't exist in this Site"
                ],200);   
            }
            return response()->json(
                [
                    'success'=>1,
                    'data'=>
                        $Client
                ],200);
        }
        $Client=$Ledger->get();
        return response()->json(
            [
                'success'=>1,
                'data'=>$Client
            ],200);
    }
}
