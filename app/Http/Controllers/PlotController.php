<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AgentDGSale;
use App\Models\AgentIncome;
use App\Models\AgentLevels;
use App\Models\AgentRegister;
use App\Models\Plot;
use App\Models\PlotTransaction;
use App\Models\Plot_Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlotController extends Controller
{
    //
    public function addPlot(Request $request)
    {
        try {
            //code...
            $params=$request->query('id');
            $plot=Plot::where('id',$params)->first();            
            $validator=Validator::make($request->all(),[
                'site_id' => $plot ? 'nullable|integer' : 'required|integer',
                'plot_No' => $plot ? 'nullable|string' : 'required|string',
                'plot_type' =>  $plot ? 'nullable|string' : 'required|string',
                'plot_area' =>  $plot ? 'nullable|string' : 'required|string',
                'price_from' =>  $plot ? 'nullable|integer' : 'required|integer',
                'price_to' =>  $plot ? 'nullable|integer' : 'required|integer',            
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

            $data=$validator->validated();

            if($plot){
                $plot->update($data);
                return response()->json([
                    'success'=>1,
                    'message' => 'Plot updated successfully',
                    'Plot' => $plot
                ], 201);
            }
            $newPlot=Plot::create($data);
            return response()->json([
                'success'=>1, 
                'message' => 'Plot Added successfully',
                'plot' => $newPlot
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['success'=>0,'details' => 'Internal Server Error. ' . $th->getMessage()], 500);
        }
    }

    public function showPlot(Request $request)
    {
        $params=$request->query('id');
        try {
            //code...
            $sales = DB::table('plots')
            ->leftJoin('plot_sales', 'plots.id', '=', 'plot_sales.plot_id')
            ->leftJoin('client_controllers', 'plot_sales.client_id', '=', 'client_controllers.id')
            ->leftJoin('sites','plots.site_id','=','sites.id')            
            ->select(
                'plots.id',
                'plots.plot_No',
                'plots.plot_type',
                'plots.plot_area',
                'plots.price_from',
                'plots.price_to',
                'plots.plot_status',
                DB::raw('IFNULL(client_controllers.client_name, \'\') AS client_name'),
                'sites.site_name'
            );
            if($params)
            {
                $sales=$sales->where('plots.id',$params);
            }
             $sales=$sales->get();
            if($sales->isEmpty()){
                return response()->json([
                    'success' => 0,
                    'error' => 'No Data Found'
                ], 404);
            }
                return response()->json(['success'=>1 ,'sales'=>$sales]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['success'=>0, 'error' => $th->getMessage()], 500);
        }
    }

    public function showPlotAdmin(Request $request)
    {
        $params = $request->query('id');
        try {
            //code...
            $sales = DB::table('plots')
            ->leftJoin('plot_sales', 'plots.id', '=', 'plot_sales.plot_id')
            ->select(
                'plots.id',
                'plots.plot_No',
                'plots.plot_type',
                'plots.plot_area',
                'plots.price_from',
                'plots.price_to',
                'plots.plot_status',
                DB::raw('IFNULL(client_controllers.client_name, \'\') AS client_name'),
            );
            if($params)
            {
                $sales=$sales->where('plots.id',$params);
            }
             $sales=$sales->get();
            if($sales->isEmpty()){
                return response()->json([
                    'success' => 0,
                    'error' => 'No Data Found'
                ], 404);
            }
                return response()->json(['success'=>1 ,'sales'=>$sales]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['success'=>0, 'error' => $th->getMessage()], 500);
        }
    }

    public function removePlot(Request $request){
        try {
            //code...
            $params=$request->query('id');
// -            $plot=Plot::where('id',$params)->first();
            $plot = Plot::find($params);
            if(!$plot)
            {
                return response()->json(['success'=>0,'error'=>"No data Found, in id {$params}"],404);
            }
            $plot->delete();
            return response()->json(['success'=>1,'message'=>'Plot Removed'],200);
        }catch (\Throwable $th) {
            return response()->json(['success'=>0,'details' => 'Internal Server Error.' . $th->getMessage()], 500);
        }
    }

    public function PlotTransaction(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'plot_sale_id' => 'required|integer',
                'amount' => 'required|integer|min:0',
                'payment_method' => 'required|string'
            ]);
        
            // if ($validator->fails()) {
            //     $errors = $validator->errors()->all(); // Get all error messages
            //     $formattedErrors = [];
        
            //     foreach ($errors as $error) {
            //         $formattedErrors[] = $error;
            //     }
        
            //     return response()->json([
            //         'success' => 0,
            //         'error' => $formattedErrors[0]
            //     ], 422);
            // } 
            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                return response()->json([
                    'success' => 0,
                    'error' => $errors[0] // Return the first error message
                ], 422);
            }  
        
            $data = $validator->validated();

            $plot_sale = Plot_Sale::findOrFail($data['plot_sale_id']);

            $plot = Plot::where('id',$plot_sale->plot_id)->first();

            if ($plot_sale->plot_status === 'COMPLETED') 
            {
                return response()->json([
                    'success' => 1,
                    'message' => 'No Payment Pending'
                ], 200);
            }
            else
            {
                $transaction_id = 'MVG' . Str::random(10);
        
                // Check if there's an existing transaction
                $existingTransaction = PlotTransaction::where('plot_sale_id', $data['plot_sale_id'])->exists();
            
                // If no existing transaction, validate amount and create a new transaction
                
                if (!$existingTransaction) {
                    if ($data['amount'] > $plot_sale->totalAmount) {
                        return response()->json([
                            'success' => 0,
                            'error' => "Amount should not be greater than {$plot_sale->totalAmount}"
                        ], 400);
                    }
            
                    $newTransaction = PlotTransaction::create([
                        'plot_sale_id' => $data['plot_sale_id'],
                        'transaction_id' => $transaction_id,
                        'amount' => $data['amount'],
                        'payment_method' => $data['payment_method']
                    ]);

                } else {
                    // Validate payment amount if there's already an existing transaction
                    $amountPaid = PlotTransaction::where('plot_sale_id', $data['plot_sale_id'])
                        ->sum(DB::raw('CAST(amount AS UNSIGNED)'));
            
                    $remainingAmount = $plot_sale->totalAmount - $amountPaid;
            
                    if ($data['amount'] > $remainingAmount) {
                        return response()->json([
                            'success' => 0,
                            'error' => "Amount paid so far: {$amountPaid}. Payment should not be greater than {$remainingAmount}"
                        ], 400);
                    }
            
                    // Create a new transaction record
                    $newTransaction = PlotTransaction::create([
                        'plot_sale_id' => $data['plot_sale_id'],
                        'transaction_id' => $transaction_id,
                        'amount' => $data['amount'],
                        'payment_method' => $data['payment_method']
                    ]);
                }
            
                // Calculate the total amount paid and determine the plot status
                $amountPaid = PlotTransaction::where('plot_sale_id', $data['plot_sale_id'])
                    ->sum(DB::raw('CAST(amount AS UNSIGNED)'));
            
                $percentagePaid = round(($amountPaid / $plot_sale->totalAmount) * 100, 2);
            
                $status = $percentagePaid < 30 ? 'PENDING' :
                          ($percentagePaid < 100 ? 'BOOKED' : 'COMPLETED');
            
                $plot_sale->update([
                    'plot_status' => $status,
                    'plot_value' => $percentagePaid
                ]);
            
                $plot->update([
                    'plot_status' => $status
                ]);
                
                if($plot_sale->plot_status === 'BOOKED')
                {
                    $CheckIncome=AgentIncome::where('plot_sale_id',$data['plot_sale_id'])->exists();
                    if(!$CheckIncome)
                    {
                        $agentDG=AgentDGSale::where('agent_id', $plot_sale->agent_id)->first();

                        if(!$agentDG)
                        {
                            AgentDGSale::create([
                                'agent_id' => $plot_sale->agent_id,
                                'direct' => 1,
                                'group'=> 1,
                            ]);
                        }else
                        {
                            $agentDG->update([
                                'direct' => $agentDG -> direct + 1,
                                'group' => $agentDG -> group + 1,
                            ]);
                        }

                        $agentParent=AgentLevels::where('agent_id', $plot_sale->agent_id)->first();
                        $agentlevel=AgentLevels::where('agent_id', $agentParent->parent_id)->first();
                        if($agentlevel->level !== "1")
                        {
                            while($agentParent)
                            {
                                $agentPDG=AgentDGSale::where('agent_id',$agentParent->parent_id)->first();
    
                                if(!$agentPDG)
                                {
                                    AgentDGSale::create([
                                        'agent_id' => $agentParent->parent_id,
                                        'direct' => 0,
                                        'group' => 1,
                                    ]);
                                }
                                else{
                                    $agentPDG->update([
                                        'group' => $agentPDG->group + 1,
                                    ]);
                                }
    
                                $agentlevel=AgentLevels::where('agent_id', $agentParent->parent_id)->first();
    
                                if($agentlevel->level === "1")
                                {
                                    break;
                                }
                                $agentParent=$agentlevel;
                            }    
                        }

                        $seller_id=$plot_sale->agent_id;
                    
                        $total_amount = $plot_sale->totalAmount; 
    
                        $agentLevel=AgentLevels::where('agent_id',$seller_id)->first();
    
                        $currentLevel=$agentLevel->level;
    
                        $incomePercentages = [
                            "1" => 8,
                            "2" => 3,
                            "3" => 2,
                            "4" => 1,
                            "5" => 1,
                            "6" => 0.70,
                            "7" => 0.60,
                            "8" => 0.40,
                            "9" => 0.20,
                            "10" => 0.10
                        ];
                        
                        // $levelTables=AgentLevels::get()
                        while ($agentLevel) {
    
                            $total_income = ($incomePercentages[$currentLevel] / 100) * $total_amount;
                            $pancard= AgentRegister::find($agentLevel->agent_id);
                            AgentIncome::create([
                                'plot_sale_id' => $plot_sale->id, 
                                'final_agent' => $agentLevel->agent_id,
                                'total_income' => $total_income,
                                'tds_deduction' => $total_income * 0.05,  // Assuming a TDS of 5%
                                'final_income' => $total_income - ($total_income * 0.05),
                                'pancard_status' => $pancard->pancard_no ? 1 : 0 ,  // Assuming you have this value
                            ]);
    
                            $parentID = $agentLevel->parent_id;
                    
                            $agentExist = AgentLevels::where('agent_id', $parentID)->first();
                    
                            if ($agentExist) {
                                $agentLevel = $agentExist;
                                $currentLevel=$agentLevel->level;
                            } else {
                                // Stop the execution if the parent ID does not exist
                                break;
                            }
                        }
                    }                    
                }

                DB::commit();

                return response()->json([
                    'success' => 1,
                    'message' => 'Transaction Added',
                    'transaction' => $newTransaction,
                    'plot_sale' => $plot_sale,
                    'plot' => $plot
                ], 201);        
            } 
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => 0,
                'error' => 'Internal Server Error. ' . $th->getMessage()
            ], 500);
        }        
    }

    public function showPlotSales(Request $request)
    {
        // $sales = DB::table('plot_sales')->get();
        $sales = DB::table('plot_sales')
            ->leftJoin('plots', 'plot_sales.plot_id', '=', 'plots.id')
            ->leftJoin('client_controllers', 'plot_sales.client_id', '=', 'client_controllers.id')
            ->leftJoin('agent_registers', 'plot_sales.agent_id', '=', 'agent_registers.id')
            ->select(
                    // 'plot_sales.*',
                    'plot_sales.id', 
                    // 'plot_sales.plot_id', 
                    'plots.plot_No',
                    'plots.plot_type',
                    'plots.plot_area',
                    'plots.price_from',
                    'plots.price_to',
                    // 'plots.plot_status',// Replace 'plot_info' with the actual column name you want from the plots table
                    // 'plot_sales.client_id', 
                    'client_controllers.client_name',  // Replace 'client_name' with the actual column name you want from the clients table
                    // 'client_controllers.client_contact',
                    // 'plot_sales.agent_id', 
                    // 'agent_registers.fullname',
                    // 'agent_registers.contact_no',
                    // 'agent_registers.pancard_no', // Replace 'agent_name' with the actual column name you want from the agents table

                    'plot_sales.totalAmount',
                    DB::raw('ROUND(plot_sales.totalAmount * (plot_sales.plot_value / 100), 2) AS calculated_value'),
                    'plot_sales.plot_status',
                    'plot_sales.plot_value',
                )
    ->get();
     if(!$sales){
        return response()->json([
            'success' => 0,
            'error' => 'No Data Found'
        ], 404);
    }
        return response()->json(['success'=>1 ,'sales'=>$sales]);
    }
}


