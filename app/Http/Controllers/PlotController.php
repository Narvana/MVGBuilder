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
    // Add and updated plot
    public function addPlot(Request $request)
    {
        try {
            //code...
            $params=$request->query('id');
            $plot=Plot::where('id',$params)->first();            
            $validator=Validator::make($request->all(),[
                'site_id' => $plot ? 'nullable|integer' : 'required|integer',
                'plot_No' => $plot ? 'nullable|string' : 'required|string', //
                'plot_type' =>  $plot ? 'nullable|string' : 'required|string',
                'plot_area' =>  $plot ? 'nullable|integer' : 'required|integer',
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
             if($data['price_from'] >= $data['price_to'])
            {
                return response()->json(['success'=>0,'error' => 'Price from should be less than Price to'], 400);
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
        $site=$request->query('site');
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
            else if($site)
            {
                $sales=$sales->where('sites.site_name',$site);
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

                $amountPaid = PlotTransaction::where('plot_sale_id', $data['plot_sale_id'])->sum('amount');
            
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
                    // $amountPaid = PlotTransaction::where('plot_sale_id', $data['plot_sale_id'])->sum(DB::raw('CAST(amount AS UNSIGNED)'));
            
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
                $amountPaid += $data['amount'];
            
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
                // $plot_sale->plot->update(['plot_status' => $status]);
                
                if($status === 'BOOKED')
                {
                    // $agentDG=AgentDGSale::where('agent_id', $plot_sale->agent_id)->first();

                    // if(!$agentDG)
                    // {
                    //     AgentDGSale::create([
                    //         'agent_id' => $plot_sale->agent_id,
                    //         'direct' => 1,
                    //         'group'=> 1,
                    //     ]);
                    // }else
                    // {
                    //     $agentDG->update([
                    //         'direct' => $agentDG -> direct + 1,
                    //         'group' => $agentDG -> group + 1,
                    //     ]);
                    // }

                    $agentDG = AgentDGSale::firstOrCreate(
                        ['agent_id' => $plot_sale->agent_id],
                        ['direct' => 0, 'group' => 0]
                    );
            
                    $agentDG->increment('direct');

                    // $agent=AgentRegister::where('id',$plot_sale->agent_id)->first();

                    // if($agentDG->designation === 'ASSOCIATE')
                    // {
                    //     if($agentDG->direct >= 2  && $agentDG->group >= 8 )
                    //     {
                    //         $incentive= 10 * 5000;
                    //         $tds=$incentive * 0.05;
                    //         $final=$incentive - $tds;
                    //         $agent->update([
                    //             'designation'=>"MANAGER"
                    //         ]);
                    //         $agentDG->update([
                    //             'designation'=>"MANAGER",
                    //             'incentive' =>  $incentive,
                    //             'tds_deduction' => $tds,
                    //             'final_incentive' => $final
                    //         ]);
                    //     }    
                    // }
                    // if($agentDG->designation === 'MANAGER')
                    // {
                    //     if($agentDG->direct >= 6  && $agentDG->group >= 36 )
                    //     {
                    //         $incentive= 32 * 3000;
                    //         $tds=$incentive * 0.05;
                    //         $final=$incentive - $tds;
                    //         $agent->update([
                    //             'designation'=>'SM'
                    //         ]);
                    //         $agentDG->update([
                    //             'designation'=>"SM",
                    //             'incentive' =>  $incentive,
                    //             'tds_deduction' => $tds,
                    //             'final_incentive' => $final
                    //         ]);
                    //     }
                    // }
                    // if($agentDG->designation === 'SM')
                    // {
                    //     $downLevel=DB::table('agent_levels')
                    //     ->leftJoin('agent_registers','agent_levels.agent_id','=','agent_registers.id')
                    //     ->select(
                    //         'agent_registers.designation'
                    //     )->where('agent_levels.referral', 'like', '%' . $agent->referral_code . '%')
                    //     ->where('agent_registers.designation','MANAGER')->count();
                        
                    //    if($downLevel >= 2)
                    //    {
                    //     if($agentDG->direct >= 11  && $agentDG->group >= 114 )
                    //      {
                    //         $incentive= 83 * 2000;
                    //         $tds=$incentive * 0.05;
                    //         $final=$incentive - $tds;
                    //         $agent->update([
                    //             'designation'=>'AGM'
                    //         ]);
                    //         $agentDG->update([
                    //             'designation'=>"AGM",
                    //             'incentive' =>  $incentive,
                    //             'tds_deduction' => $tds,
                    //             'final_incentive' => $final
                    //         ]);
                    //      }
                    //    }
                        
                    // }
                    // if($agentDG->designation === 'AGM')
                    // {
                    //     $downLevel=DB::table('agent_levels')
                    //     ->leftJoin('agent_registers','agent_levels.agent_id','=','agent_registers.id')
                    //     ->select(
                    //         DB::raw('COUNT(CASE WHEN agent_registers.designation = "AGM" THEN 1 END) as AGM_count'),
                    //         DB::raw('COUNT(CASE WHEN agent_registers.designation = "SM" THEN 1 END) as sm_count')
                    //     )->where('agent_levels.referral', 'like', '%' . $agent->referral_code . '%')->first(); 
                                    
                    //   if($downLevel->AGM_count >= 1 && $downLevel->sm_count >=2 )
                    //   {
                    //     if($agentDG->direct >= 17  && $agentDG->group >= 282 )
                    //     {
                    //       $incentive= 174 * 1500;
                    //       $tds=$incentive * 0.05;
                    //       $final=$incentive - $tds;
                    //       $agent->update([
                    //           'designation'=>'GM'
                    //       ]);
                    //       $agentDG->update([
                    //           'designation'=>"GM",
                    //           'incentive' =>  $incentive,
                    //           'tds_deduction' => $tds,
                    //           'final_incentive' => $final,
                    //         //   'salary' => 40000,
                    //       ]);
                    //     } 
                    //   }


                    // }
                    // if($agentDG->designation === 'GM')
                    // {
                    //     $downLevel=DB::table('agent_levels')
                    //     ->leftJoin('agent_registers','agent_levels.agent_id','=','agent_registers.id')
                    //     ->select(
                    //         DB::raw('COUNT(CASE WHEN agent_registers.designation = "AGM" THEN 1 END) as AGM_count'),
                    //         DB::raw('COUNT(CASE WHEN agent_registers.designation = "SM" THEN 1 END) as sm_count')
                    //     )->where('agent_levels.referral', 'like', '%' . $agent->referral_code . '%')->first(); 

                    //   if($downLevel->AGM_count >= 2 && $downLevel->sm_count >=4 )
                    //   {
                    //     if($agentDG->direct >= 24  && $agentDG->group >= 600 )
                    //     {
                    //         $incentive= 325 * 1000;
                    //         $tds=$incentive * 0.05;
                    //         $final=$incentive - $tds;
                    //         $agent->update([
                    //             'designation'=>'SGM'
                    //         ]);
                    //         $agentDG->update([
                    //             'designation'=>"SGM",
                    //             'incentive' =>  $incentive,
                    //             'tds_deduction' => $tds,
                    //             'final_incentive' => $final,
                    //             'salary' => 70000,
                    //         ]);
                    //     }
                    //   }
                    // }



                    $agentLevel=AgentLevels::where('agent_id', $plot_sale->agent_id)->first(); //2
                    $Level=$agentLevel;
                    while ($agentLevel && $agentLevel->parent_id) 
                    {
                        $parentLevel = AgentLevels::where('agent_id', $agentLevel->parent_id)->first();
                        if (!$parentLevel) break;
            
                        $agentPDG = AgentDGSale::firstOrCreate(
                            ['agent_id' => $parentLevel->agent_id],
                            ['direct' => 0, 'group' => 0]
                        );
                        $agentPDG->increment('group');
                        // $agentParent=AgentRegister::where('id',$agentLevel->parent_id)->first();
                       
                        // if($agentPDG->designation === 'ASSOCIATE')
                        // {
                        //     if($agentPDG->direct >= 2  && $agentPDG->group >=8 )
                        //     {
                        //         $incentive= 10 * 5000;
                        //         $tds=$incentive * 0.05;
                        //         $final=$incentive - $tds;
                        //         $agentParent->update([
                        //             'designation'=>'MANAGER'
                        //         ]);
                        //         $agentPDG->update([
                        //             'designation'=>"MANAGER",
                        //             'incentive' =>  $incentive,
                        //             'tds_deduction' => $tds,
                        //             'final_incentive' => $final
                        //         ]);
                        //     }    
                        // }
                        // if($agentPDG->designation === 'MANAGER')
                        // {
                        //     if($agentPDG->direct >= 6  && $agentPDG->group >= 36 )
                        //     {
                        //         $incentive= 32 * 3000;
                        //         $tds=$incentive * 0.05;
                        //         $final=$incentive - $tds;
                        //         $agentParent->update([
                        //             'designation'=>'SM'
                        //         ]);
                        //         $agentPDG->update([
                        //             'designation'=>"SM",
                        //             'incentive' =>  $incentive,
                        //             'tds_deduction' => $tds,
                        //             'final_incentive' => $final
                        //         ]);
                        //     }
                        // }
                        // if($agentPDG->designation === 'SM')
                        // {
                        //     $downLevel=DB::table('agent_levels')
                        //     ->leftJoin('agent_registers','agent_levels.agent_id','=','agent_registers.id')
                        //     ->select(
                        //         'agent_registers.designation'
                        //     )->where('agent_levels.referral', 'like', '%' . $agentParent->referral_code . '%')
                        //     ->where('agent_registers.designation','MANAGER')->count();
                            
                        //    if($downLevel >= 2)
                        //    {
                        //     if($agentPDG->direct >= 11  && $agentPDG->group >= 114 )
                        //      {
                        //         $incentive= 83 * 2000;
                        //         $tds=$incentive * 0.05;
                        //         $final=$incentive - $tds;
                        //         $agentParent->update([
                        //             'designation'=>'AGM'
                        //         ]);
                        //         $agentPDG->update([
                        //             'designation'=>"AGM",
                        //             'incentive' =>  $incentive,
                        //             'tds_deduction' => $tds,
                        //             'final_incentive' => $final
                        //         ]);
                        //      }
                        //    }
                            
                        // }
                        // if($agentPDG->designation === 'AGM')
                        // {
                        //     $downLevel=DB::table('agent_levels')
                        //     ->leftJoin('agent_registers','agent_levels.agent_id','=','agent_registers.id')
                        //     ->select(
                        //         DB::raw('COUNT(CASE WHEN agent_registers.designation = "AGM" THEN 1 END) as AGM_count'),
                        //         DB::raw('COUNT(CASE WHEN agent_registers.designation = "SM" THEN 1 END) as sm_count')
                        //     )->where('agent_levels.referral', 'like', '%' . $agentParent->referral_code . '%')->first(); 
                                        
                        //   if($downLevel->AGM_count >= 1 && $downLevel->sm_count >=2 )
                        //   {
                        //     if($agentPDG->direct >= 17  && $agentPDG->group >= 282 )
                        //     {
                        //       $incentive= 174 * 1500;
                        //       $tds=$incentive * 0.05;
                        //       $final=$incentive - $tds;
                        //       $agentParent->update([
                        //           'designation'=>'GM'
                        //       ]);
                        //       $agentPDG->update([
                        //           'designation'=>"GM",
                        //           'incentive' =>  $incentive,
                        //           'tds_deduction' => $tds,
                        //           'final_incentive' => $final
                        //       ]);
                        //     } 
                        //   }
    
    
                        // }
                        // if($agentPDG->designation === 'GM')
                        // {
                        //     $downLevel=DB::table('agent_levels')
                        //     ->leftJoin('agent_registers','agent_levels.agent_id','=','agent_registers.id')
                        //     ->select(
                        //         DB::raw('COUNT(CASE WHEN agent_registers.designation = "AGM" THEN 1 END) as AGM_count'),
                        //         DB::raw('COUNT(CASE WHEN agent_registers.designation = "SM" THEN 1 END) as sm_count')
                        //     )->where('agent_levels.referral', 'like', '%' . $agentParent->referral_code . '%')->first(); 
    
                        //   if($downLevel->AGM_count >= 2 && $downLevel->sm_count >=4 )
                        //   {
                        //     if($agentPDG->direct >= 24  && $agentPDG->group >= 600 )
                        //     {
                        //         $incentive= 325 * 1000;
                        //         $tds=$incentive * 0.05;
                        //         $final=$incentive - $tds;
                        //         $agentParent->update([
                        //             'designation'=>'SGM'
                        //         ]);
                        //         $agentPDG->update([
                        //             'designation'=>"SGM",
                        //             'incentive' =>  $incentive,
                        //             'tds_deduction' => $tds,
                        //             'final_incentive' => $final
                        //         ]);
                        //     }
                        //   }
                        // }
                            
                        $agentLevel = $parentLevel;
                    }

                    // $agentDGINFO=AgentDGSale::where('agent_id',$plot_sale->agent_id)->first();
                    // $agent=AgentRegister::where('id',$plot_sale->agent_id)->first();
                   
                    $CheckIncome=AgentIncome::where('plot_sale_id',$data['plot_sale_id'])->first();
                    if(!$CheckIncome)
                    {
                        // $seller_id=$plot_sale->agent_id;
                    
                        $total_amount = $plot_sale->totalAmount; 
    
                        // $agentlevel=AgentLevels::where('agent_id',$seller_id)->first();

                        $currentLevel=$Level->level;
                        // return response()->json($currentLevel);
                        
                        $incomePercentages = [
                            "1" => 8,"2" => 3,"3" => 2,"4" => 1,"5" => 1,"6" => 0.70,"7" => 0.60,"8" => 0.40,"9" => 0.20,"10" => 0.10
                        ];
                        
                        // $levelTables=AgentLevels::get()
                        while ($Level) {
                            $total_income = ($incomePercentages[$currentLevel] / 100) * $total_amount;
                            $pancard= AgentRegister::find($Level->agent_id);
                            AgentIncome::create([
                                'plot_sale_id' => $plot_sale->id, 
                                'final_agent' => $Level->agent_id,
                                'total_income' => $total_income,
                                'tds_deduction' => $total_income * 0.05,  // Assuming a TDS of 5%
                                'final_income' => $total_income - ($total_income * 0.05),
                                'pancard_status' => $pancard->pancard_no ? 1 : 0 ,  // Assuming you have this value
                            ]);
    
                            $parentID = $Level->parent_id;
                    
                            $agentExist = AgentLevels::where('agent_id', $parentID)->first();
                    
                            if ($agentExist) {
                                $Level = $agentExist;
                                $currentLevel=$Level->level;
                            } else {
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
            ->leftJoin('plot_transactions','plot_transactions.plot_sale_id','=','plot_sales.id')
            ->leftJoin('client_controllers', 'plot_sales.client_id', '=', 'client_controllers.id')
            ->leftJoin('agent_registers', 'plot_sales.agent_id', '=', 'agent_registers.id')
            ->select(
                    // 'plot_sales.*',
                    'plot_sales.id', 
                    'plots.plot_No',
                    'plots.plot_type',
                    'plots.plot_area',
                    'plots.price_from',
                    'plots.price_to',
                    'agent_registers.fullname As Broker_Name',
                    'client_controllers.client_name',  // Replace 'client_name' with the actual column name you want from the clients table
                    'plot_sales.initial_amount',
                    'plot_sales.totalAmount',
                    DB::raw('ROUND(SUM(plot_transactions.amount), 2) AS calculated_value'),
                    'plot_sales.plot_status',
                    'plot_sales.plot_value',
                )->groupBy(
                    'plot_sales.id',
                    'plots.plot_No',
                    'plots.plot_type',
                    'plots.plot_area',
                    'plots.price_from',
                    'plots.price_to',
                    'agent_registers.fullname',
                    'client_controllers.client_name',
                    'plot_sales.initial_amount',
                    'plot_sales.totalAmount',
                    'plot_sales.plot_status',
                    'plot_sales.plot_value'
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



// DG Sale

// $agentParent=AgentLevels::where('agent_id', $agentlevel->parent_id)->first(); //1 
// if($agentlevel->level > "1" && $agentParent->level !== null)
// {
//    while($agentlevel)
//     {
//         $agentPDG=AgentDGSale::where('agent_id',$agentlevel->parent_id)->first();

//         $agentPDG = AgentDGSale::firstOrCreate(
//             ['agent_id' => $agentlevel->parent_id],
//             ['direct' => 0, 'group' => 0]
//         );
//         $agentPDG->increment('group');

//         $agent=AgentLevels::where('agent_id', $agentlevel->parent_id)->first();

//         if($agent->level === '1')
//         {
//             break;
//         }
//         // if($agent->)
//         $agentlevel=$agent;
//     }        
// }



// // Fetch or create agent's DG sale record
// $agentDG = AgentDGSale::firstOrCreate(
//     ['agent_id' => $plot_sale->agent_id],
//     ['direct' => 0, 'group' => 0]
// );

// // Increment direct and group count
// $agentDG->increment('direct');
// $agentDG->increment('group');

// // Fetch agent's record
// $agent = AgentRegister::find($plot_sale->agent_id);

// // Define the conditions for promotions and incentives
// $designations = [
//     'ASSOCIATE' => ['next' => 'MANAGER', 'direct' => 2, 'group' => 10, 'incentive' => 5000],
//     'MANAGER' => ['next' => 'SM', 'direct' => 6, 'group' => 42, 'incentive' => 3000],
//     'SM' => ['next' => 'AGM', 'direct' => 11, 'group' => 125, 'incentive' => 2000],
//     'AGM' => ['next' => 'GM', 'direct' => 17, 'group' => 299, 'incentive' => 1500],
//     'GM' => ['next' => 'SGM', 'direct' => 24, 'group' => 624, 'incentive' => 1000]
// ];

// // Check for promotion conditions
// if (isset($designations[$agentDG->designation])) {
//     $criteria = $designations[$agentDG->designation];
//     if ($agentDG->direct >= $criteria['direct'] && $agentDG->group >= $criteria['group']) {
//         $incentive = $criteria['group'] * $criteria['incentive'];
//         $tds = $incentive * 0.05;
//         $final = $incentive - $tds;

//         $agent->update(['designation' => $criteria['next']]);
//         $agentDG->update([
//             'designation' => $criteria['next'],
//             'incentive' => $incentive,
//             'tds_deduction' => $tds,
//             'final_incentive' => $final
//         ]);
//     }
// }

// // Update the group count for parent agents
// $agentLevel = AgentLevels::where('agent_id', $plot_sale->agent_id)->first();
// while ($agentLevel && $agentLevel->parent_id) {
//     $parentLevel = AgentLevels::find($agentLevel->parent_id);
//     if (!$parentLevel) break;

//     $agentPDG = AgentDGSale::firstOrCreate(
//         ['agent_id' => $parentLevel->agent_id],
//         ['direct' => 0, 'group' => 0]
//     );
//     $agentPDG->increment('group');

//     $agentParent = AgentRegister::find($agentLevel->parent_id);

//     if (isset($designations[$agentPDG->designation])) {
//         $criteria = $designations[$agentPDG->designation];
//         if ($agentPDG->direct >= $criteria['direct'] && $agentPDG->group >= $criteria['group']) {
//             $incentive = $criteria['group'] * $criteria['incentive'];
//             $tds = $incentive * 0.05;
//             $final = $incentive - $tds;

//             $agentParent->update(['designation' => $criteria['next']]);
//             $agentPDG->update([
//                 'designation' => $criteria['next'],
//                 'incentive' => $incentive,
//                 'tds_deduction' => $tds,
//                 'final_incentive' => $final
//             ]);
//         }
//     }

//     $agentLevel = $parentLevel;
// }
