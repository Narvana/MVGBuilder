<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\AgentProfile;
use App\Models\ClientController;
use App\Models\ClientEMIInfo;
use App\Models\ClientInvoice;
use App\Models\Plot_Sale;
use App\Models\Plot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
                ],400);
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
            'plots.plot_No',
            'plots.plot_area',
            'plot_sales.buying_type',
            DB::raw('plot_sales.totalAmount/plots.plot_area baseAmount'),
            'plot_sales.totalAmount',
            'plot_transactions.transaction_id',
            'plot_transactions.amount',
            DB::raw('DATE(plot_transactions.created_at) as transaction_date')
        )
        ->where('plot_sales.plot_value', '>', '0')
        ->whereIn('sites.id',[$siteID]);

        if($id)
        {
          $Client=$Ledger->where('client_controllers.id',$id)->get();
            if($Client->isEmpty())
            {
                return response()->json(
                [
                    'success'=>0,
                    'message'=> "No Ledger Found Regarding this Client"
                ],404);
            }
            return response()->json(
                [
                    'success'=>1,
                    'data'=>
                        $Client
                ],200);
        }
        $Client=$Ledger->get();
        if($Client->isEmpty())
        {
            return response()->json(
            [
                'success'=>0,
                'message'=> "No Ledger Found Regarding this Client"
            ],404);   
        }
        return response()->json(
        [
            'success'=>1,
            'data'=>$Client
        ],200);
    }

    public function ClientInvoiceADMIN(Request $request)
    {
        $id= $request->query('id');
        if(!$id)
        {
            return response()->json( [
                'success'=>0,
                'message'=> "Please Select a Name of the Client"
            ],404);
        }

        $ClientLegder = DB::table('client_controllers')
        ->leftJoin('plot_sales', 'client_controllers.id', '=', 'plot_sales.client_id')
        ->leftJoin('plots', 'plot_sales.plot_id', '=', 'plots.id')
        ->leftJoin('sites', 'plots.site_id', '=', 'sites.id')
        ->leftJoin('agent_registers','plot_sales.agent_id','=','agent_registers.id')
        ->leftJoin('plot_transactions', 'plot_sales.id', '=', 'plot_transactions.plot_sale_id')
        ->select(
            'client_controllers.id',
            'client_controllers.client_name',
            'client_controllers.client_contact',
            DB::raw('CONCAT(client_controllers.client_address, ", ", client_controllers.client_city, ", ", client_controllers.client_state) as Address'),
            'sites.site_name',
            'plots.plot_No',
            'plots.plot_area',
            'plot_transactions.transaction_id',
            'plot_transactions.amount',
            DB::raw('DATE(plot_transactions.created_at) as transaction_date'),
            'agent_registers.fullname as Agent'
        )
        ->where('plot_sales.plot_value', '>', '0')
        ->where('client_controllers.id',$id)
        ->get();

        if($ClientLegder->isEmpty())
        {
            return response()->json(
            [
                'success'=> 0,
                'message'=> "No Ledger Found Regarding this Client"
            ],404);   
        }
        return response()->json(
        [
            'success'=> 1,
            'data'=>$ClientLegder
        ],200);
    }

    public function CreateINVOICE(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'Client_name'=>'required|string',
                'Client_contact'=>'required|string',
                'Client_address'=>'required|string',
                'Site_Name'=>'required|string',
                'Plot_No'=>'required|string',
                'Plot_Area'=>'required|string',
                'Transaction_id'=>'required|string',
                'Amount'=>'required|string',
                'Transaction_date'=>'required|string',
                'Agent_name'=>'required|string',
                'Payment_Method'=>'required|string',
                'Payment_Detail'=>'required|string'
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

                $length=ClientInvoice::count();
               
                $data['Invoice_no'] = 'MVG' . $length + 1;
                // $data['Transaction_id']="0";
                $ClientInvoice=ClientInvoice::create($data);
                return response()->json([
                    'success'=> 1,
                    'data'=>$ClientInvoice
                ],200);        
        } catch (\Throwable $th) {
            return response()->json([
                'success'=>0, 'error' => 'Internal Server Error' . $th->getMessage()], 500);
        }
    }

    public function GetInvoices(Request $request)
    {
        // $id=$request->query('id');

            $invoices=ClientInvoice::get();

        return response()->json([
           'success'=> 1,
           'data'=>$invoices
        ],200);   
    }

    public function DailyTransactionClient(Request $request)
    {   

        // $today = Carbon::today()->toDateString();  
        // return response()->json($today);
        
        $date= $request->query('date');

        if(!$date)
        {
            return response()->json(
            [
                'success'=> 0,
                'message'=> "Please select a date"
            ],404); 
        }

        $Client = DB::table('client_controllers')
        ->leftJoin('plot_sales', 'client_controllers.id', '=', 'plot_sales.client_id')
        ->leftJoin('plots', 'plot_sales.plot_id', '=', 'plots.id')
        ->leftJoin('sites', 'plots.site_id', '=', 'sites.id')
        ->leftJoin('plot_transactions', 'plot_sales.id', '=', 'plot_transactions.plot_sale_id')
        ->select(
            'client_controllers.id',
            'client_controllers.client_name',
            'client_controllers.client_contact',
            'sites.site_name',
            'plots.plot_No',
            'plots.plot_area',
            'plot_sales.plot_value',
            'plot_transactions.transaction_id',
            'plot_transactions.amount',
            DB::raw('DATE(plot_transactions.created_at) as transaction_date')
        )
        ->where('plot_sales.plot_value', '>', '0')
        ->whereDate('plot_transactions.created_at', $date)
        ->get();
        
        if($Client->isEmpty())
        {
            return response()->json(
            [
                'success'=> 0,
                'message'=> "No Transaction Found for the selected date"
            ],404);   
        }
        return response()->json(
        [
            'success'=>1,
            'data'=>$Client
        ],200);
    }

    public function ClientLists(Request $request)
    {
        $Client = DB::table('client_controllers')
                  ->select(
                    'client_controllers.id', 
                    'client_controllers.client_name'
                  )->get(); 

        if($Client->isEmpty())
        {
            return response()->json(
            [
                'success'=> 0,
                'message'=> "No Client found"
            ],404);   
        }
        return response()->json(
        [
            'success'=>1,
            'data'=>$Client
        ],200);
    }

    public function AddClientEMI(Request $request)
    {
        try {
            $id = $request->query('id');
            if (!$id) {
                return response()->json([
                    'success' => 0,
                    'message' => "Please select or provide the id to add client EMI info"
                ], 400);
            }
            
            $ClientEMI = ClientEMIInfo::where('id', $id)->first();
            if (!$ClientEMI) {
                return response()->json([
                    'success' => 0,
                    'message' => "No EMI Information found for this id"
                ], 400);
            }
            
            $validator = Validator::make($request->all(), [
                'EMI_Amount' => [
                    'required',
                    'numeric',
                    Rule::when($ClientEMI->EMI_Amount !== "0.00", ['prohibited']),
                ],
                'EMI_Date' => [
                    'required',
                    'date',
                    Rule::when($ClientEMI->EMI_Date !== null, ['prohibited']),
                ],
            ], [
                'EMI_Amount.prohibited' => 'You cannot update the EMI Amount once it has been set.',
                'EMI_Date.prohibited' => 'You cannot update the EMI Date once it has been set.'
            ]);
            
            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                return response()->json([
                    'success' => 0,
                    'error' => $errors[0]
                ], 422);
            }
            
            $data = $validator->validated();
            $ClientEMI->update($data);
            
            return response()->json([
                'success' => 1,
                'message' => 'EMI information updated successfully'
            ], 200);
            
        } catch (\Throwable $th) {
            return response()->json(['success'=>0, 'error' => $th->getMessage()], 500);
        }
    }

    public function UpdateEMIDate(Request $request)
    {
        $today = Carbon::today()->toDateString();  

        $EMI = ClientEMIInfo::get();
        
        if($EMI->isEmpty())
        {
          return response()->json(
            [
                'success'=> 0,
                'message'=> "No EMI information found"
            ],404);   
        }

        $count=0;

        foreach($EMI as $emi)
        {
            $plot_sale=Plot_Sale::where('id',$emi->plot_sale_id)->first();

            if($plot_sale->plot_value == 100.00 && $plot_sale->plot_status === 'COMPLETED')
            {
                continue;                    
            }
            else{
                if($emi->EMI_Date == null)
                {
                    continue;   
                }
                else if($emi->EMI_Date != null &&  $emi->EMI_Date <= $today) {
                    $newEMIDate = Carbon::parse($emi->EMI_Date)->addMonth();        
                    $emi->update([
                        'EMI_Date' => $newEMIDate->toDateString(),
                    ]);
                    $count++;
                }
            }
        } 

        if($count === 0)
        {
            return response()->json([
                'success'=> 0,
                'message'=> "No EMI dates updated Today $today"
            ], 400);        
        }
        return response()->json([
            'success'=> 1,
            'message'=> "Total $count EMI dates updated successfully"
        ], 200);
    }

    public function GetClientEMI(Request $request)
    {

        $date = $request->query('date');


        $ClientEMI= DB::table('client_e_m_i_infos')
        ->leftJoin('plot_sales','plot_sales.id','=','client_e_m_i_infos.plot_sale_id')
        ->leftJoin('client_controllers','client_controllers.id','=','plot_sales.client_id')
        ->leftJoin('plots','plots.id','=','plot_sales.plot_id')
        ->select(
            'client_e_m_i_infos.id',
            'client_controllers.client_name',
            'client_controllers.client_contact',
            'plots.plot_No',
            'client_e_m_i_infos.EMI_amount',
            'client_e_m_i_infos.EMI_Date',
            DB::raw('ROUND((plot_sales.totalAmount * ((100 - client_e_m_i_infos.EMI_Start_at)/100))/client_e_m_i_infos.EMI_amount) as Months')
        );
         
        if($date)
        {
            $data=$ClientEMI->where('client_e_m_i_infos.EMI_Date',$date)->get();
            if($data->isEmpty())
            {
                return response()->json(
                    [
                        'success' => 0,
                        'message' => "No EMI found"
                    ],404);
            }
            return response()->json(
                [
                    'success' => 1,
                    'data' => $data
                ],200);
        }
        else
        {
            $Clientdata= $ClientEMI->get();
            if($Clientdata->isEmpty()){
            return response()->json(
                [
                    'success'=> 0,
                    'message'=> "No EMI Generated"
                ],404);
            }
            return response()->json(
            [
                'success'=>1,
                'data' => $Clientdata
            ],200);
        }
    
        // if($search)
        // {
        //    $data = $ClientEMI->where('client_controllers.client_name', 'like', "%{$search}%")
        // ->orWhere('client_controllers.client_contact', 'like', "%{$search}%")
        // ->orWhere('plots.plot_No', 'like', "%{$search}%")
        // ->get();
        //     if(!$data)
        //     {
        //         return response()->json(
        //             [
        //                 'success' => 0,
        //                 'message' => "No EMI found on date {$date}"
        //             ],404);
        //     }
        //     return response()->json(
        //     [
        //         'success'=>1,
        //         'data'=>$data
        //     ],200);
        // }
    }
}
