<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
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

            $VALUE=$plot_sale->plot_value ? $plot_sale->plot_value : null;

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
                
                $CheckIncome=AgentIncome::where('plot_sale_id',$data['plot_sale_id'])->get();

                if($status === 'BOOKED' && $plot_sale->TDG_status === 0)
                {
                    $agentDG = AgentDGSale::firstOrCreate(
                        ['agent_id' => $plot_sale->agent_id],
                        ['direct' => 0, 'group' => 0]
                    );
            
                    $agentDG->increment('direct');

                    $agent=AgentRegister::where('id',$plot_sale->agent_id)->first();

                    if($agentDG->designation === 'ASSOCIATE')
                    {
                        if($agentDG->direct >= 2  && $agentDG->group >= 8 )
                        {
                            $downAgent=AgentLevels::where('parent_id',$agent->id)->get();

                            foreach ($downAgent as $dagent) 
                            {
                                // Access each agent's properties
                               $agentID=$dagent->agent_id;
                               $DGsale=AgentDGSale::where('agent_id',$agentID)->first();
                               $Total=$DGsale->direct + $DGsale->group;
                               if($Total >= 4)
                               {
                                    $incentive= 10 * 5000;
                                    $tds=$incentive * 0.05;
                                    $final=$incentive - $tds;
                                    $agent->update([
                                        'designation'=>"MANAGER"
                                    ]);
                                    $agentDG->update([
                                        'designation'=>"MANAGER",
                                        'incentive' =>  $incentive,
                                        'tds_deduction' => $tds,
                                        'final_incentive' => $final
                                    ]);
                                 break; 
                               }
                            }
                        }
                    }
                    else if($agentDG->designation === 'MANAGER')
                    {
                       if($agentDG->direct >= 6  && $agentDG->group >= 36)
                        {
                            $downAgent=AgentLevels::where('parent_id',$agent->id)->get();

                            foreach ($downAgent as $dagent) 
                            {
                                // Access each agent's properties
                               $agentID=$dagent->agent_id;
                               $DGsale=AgentDGSale::where('agent_id',$agentID)->first();
                               $Total=$DGsale->direct + $DGsale->group;

                               if($Total >= 18 )
                               {
                                $DAgent=AgentLevels::where('parent_id',$agent->id)->get();
                                $incentive=0;
                                foreach($DAgent as $DA)
                                {
                                  $DAid=$DA->agent_id;
                                  $DGS=AgentDGSale::where('agent_id',$DAid)->first();
                                  $DGtotal=$DGS->direct + $DGS->group;
                                  if($DGS->designation === 'MANAGER')
                                  {
                                      $incentive=$incentive + ($DGtotal * 3000);
                                  }
                                  else if($DGS->designation === 'ASSOCIATE')
                                  {
                                      $incentive=$incentive + ($DGtotal * 8000);
                                  }
                                }
                                 
                                    $totalincentive= $incentive;
                                    $tds=$totalincentive * 0.05;
                                    $final=$totalincentive - $tds;
                                    $agent->update([
                                        'designation'=>'SM'
                                    ]);
                                    $agentDG->update([
                                        'designation'=>"SM",
                                        'incentive' =>  $totalincentive,
                                        'tds_deduction' => $tds,
                                        'final_incentive' => $final
                                    ]);
                                    break;
                                } 
                            }
                        }
                    }
                    else if($agentDG->designation === 'SM')
                    {
                      $downLevel=DB::table('agent_levels')
                      ->leftJoin('agent_registers','agent_levels.agent_id','=','agent_registers.id')
                      ->select(
                        'agent_registers.designation'
                        )->where('agent_levels.referral', 'like', '%' . $agent->referral_code . '%')
                        ->where('agent_registers.designation','MANAGER')->count();
                        
                       if($downLevel >= 2)
                       {
                            if($agentDG->direct >= 11  && $agentDG->group >= 114)
                            { 
                                $downAgent=AgentLevels::where('parent_id',$agent->id)->get();
                                
                                foreach ($downAgent as $dagent) 
                                {
                                    // Access each agent's properties
                                    $agentID=$dagent->agent_id;
                                    $DGsale=AgentDGSale::where('agent_id',$agentID)->first();
                                    $Total=$DGsale->direct + $DGsale->group;
                                    
                                    if($Total >= 57)
                                    {                                
                                        $DAgent=AgentLevels::where('parent_id',$agent->id)->get();
                                        $incentive=0;
                                        foreach($DAgent as $DA)
                                        {
                                          $DAid=$DA->agent_id;
                                          $DGS=AgentDGSale::where('agent_id',$DAid)->first();
                                          $DGtotal=$DGS->direct + $DGS->group;
                                          if($DGS->destination === 'SM')
                                          {
                                            $incentive=$incentive + ($DGtotal * 2000);
                                          }
                                          else if($DGS->designation === 'MANAGER')
                                          {
                                              $incentive=$incentive + ($DGtotal * (3000+2000));
                                          }
                                          else if($DGS->designation === 'ASSOCIATE')
                                          {
                                              $incentive=$incentive + ($DGtotal * (5000+3000+2000));
                                          }
                                        }
        

                                        $totalincentive= $incentive;
                                        $tds=$totalincentive * 0.05;
                                        $final=$totalincentive - $tds;
                                        $agent->update([
                                            'designation'=>'AGM'
                                        ]);
                                        $agentDG->update([
                                            'designation'=>"AGM",
                                            'incentive' =>  $totalincentive,
                                            'tds_deduction' => $tds,
                                            'final_incentive' => $final
                                        ]);

                                        break;
                                    }
                                }
                            }
                        }
                    }
                    else if($agentDG->designation === 'AGM')
                    {
                        $downLevel=DB::table('agent_levels')
                        ->leftJoin('agent_registers','agent_levels.agent_id','=','agent_registers.id')
                        ->select(
                            DB::raw('COUNT(CASE WHEN agent_registers.designation = "AGM" THEN 1 END) as AGM_count'),
                            DB::raw('COUNT(CASE WHEN agent_registers.designation = "SM" THEN 1 END) as sm_count')
                        )->where('agent_levels.referral', 'like', '%' . $agent->referral_code . '%')->first(); 
                                    
                      if($downLevel->AGM_count >= 1 && $downLevel->sm_count >=2 )
                      {
                        if($agentDG->direct >= 17  && $agentDG->group >= 282 )
                        {
                            $downAgent=AgentLevels::where('parent_id',$agent->id)->get();

                            foreach ($downAgent as $dagent) 
                            {
                                // Access each agent's properties
                               $agentID=$dagent->agent_id;
                               $DGsale=AgentDGSale::where('agent_id',$agentID)->first();
                               $Total=$DGsale->direct + $DGsale->group;
                               if($Total >= 141)
                               {
                                    $DAgent=AgentLevels::where('parent_id',$agent->id)->get();
                                    $incentive=0;
                                    foreach($DAgent as $DA)
                                    {
                                        $DAid=$DA->agent_id;
                                        $DGS=AgentDGSale::where('agent_id',$DAid)->first();
                                        $DGtotal=$DGS->direct + $DGS->group;
                                        if($DGS->designation === 'AGM'){
                                            $incentive=$incentive + ($DGtotal * 1500);
                                        }
                                        else if($DGS->designation === 'SM')
                                        {
                                            $incentive = $incentive + ($DGtotal * (1500 + 2000));
                                        }
                                        else if($DGS->designation === 'MANAGER')
                                        {
                                            $incentive=$incentive + ($DGtotal * (1500 + 2000 + 3000));
                                        }
                                        else if($DGS->designation === 'ASSOCIATE')
                                        {
                                            $incentive=$incentive + ($DGtotal * (1500 + 2000 + 3000 + 5000));
                                        }
                                    }

                                
                                    $totalincentive= $incentive;
                                    $tds=$totalincentive * 0.05;
                                    $final=$totalincentive - $tds;
                                    $agent->update([
                                        'designation'=>'GM'
                                    ]);
                                    $agentDG->update([
                                        'designation'=>"GM",
                                        'incentive' =>  $totalincentive,
                                        'tds_deduction' => $tds,
                                        'final_incentive' => $final,
                                        'salary' => 40000,
                                    ]);
                                    break;
                                }
                            }
                        } 
                      }


                    }
                    else if($agentDG->designation === 'GM')
                    {
                        $downLevel=DB::table('agent_levels')
                        ->leftJoin('agent_registers','agent_levels.agent_id','=','agent_registers.id')
                        ->select(
                            DB::raw('COUNT(CASE WHEN agent_registers.designation = "AGM" THEN 1 END) as AGM_count'),
                            DB::raw('COUNT(CASE WHEN agent_registers.designation = "SM" THEN 1 END) as sm_count')
                        )->where('agent_levels.referral', 'like', '%' . $agent->referral_code . '%')->first(); 

                      if($downLevel->AGM_count >= 2 && $downLevel->sm_count >=4 )
                      {
                        if($agentDG->direct >= 24  && $agentDG->group >= 600 )
                        {
                            $downAgent=AgentLevels::where('parent_id',$agent->id)->get();

                            foreach ($downAgent as $dagent) 
                            {
                                // Access each agent's properties
                               $agentID=$dagent->agent_id;
                               $DGsale=AgentDGSale::where('agent_id',$agentID)->first();
                               $Total=$DGsale->direct + $DGsale->group;
                               if($Total >= 300)
                               {
                                    $DAgent=AgentLevels::where('parent_id',$agent->id)->get();
                                    $incentive=0;
                                    foreach($DAgent as $DA)
                                    {
                                        $DAid=$DA->agent_id;
                                        $DGS=AgentDGSale::where('agent_id',$DAid)->first();
                                        $DGtotal=$DGS->direct + $DGS->group;
                                        if($DGS->designation === 'GM')
                                        {
                                            $incentive = $incentive + ($DGtotal * 1000);
                                        }
                                        if($DGS->designation === 'AGM'){
                                            $incentive=$incentive + ($DGtotal * (1000 + 1500));
                                        }
                                        else if($DGS->designation === 'SM')
                                        {
                                            $incentive = $incentive + ($DGtotal * (1000 + 1500 + 2000));
                                        }
                                        else if($DGS->designation === 'MANAGER')
                                        {
                                            $incentive=$incentive + ($DGtotal * (1000 + 1500 + 2000 + 3000));
                                        }
                                        else if($DGS->designation === 'ASSOCIATE')
                                        {
                                            $incentive=$incentive + ($DGtotal * (1000 + 1500 + 2000 + 3000 + 5000));
                                        }
                                    }

                                    $totalincentive= $incentive;
                                    $tds=$totalincentive * 0.05;
                                    $final=$totalincentive - $tds;
                                    $agent->update([
                                        'designation'=>'SGM'
                                    ]);
                                    $agentDG->update([
                                        'designation'=>"SGM",
                                        'incentive' =>  $totalincentive,
                                        'tds_deduction' => $tds,
                                        'final_incentive' => $final,
                                        'salary' => 70000,
                                    ]);
                                    break;
                                }
                            }
                        }
                      }
                    }

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

                        $agentParent=AgentRegister::where('id',$agentLevel->parent_id)->first();
                       
                        if($agentPDG->designation === 'ASSOCIATE')
                        {
                            if($agentPDG->direct >= 2  && $agentPDG->group >=8)
                            {
                                $downAgent=AgentLevels::where('parent_id',$agentParent->id)->get();

                                foreach ($downAgent as $dagent) 
                                {
                                    // Access each agent's properties
                                   $agentID=$dagent->agent_id;
                                   $DGsale=AgentDGSale::where('agent_id',$agentParent->id)->first();
                                   $Total=$DGsale->direct + $DGsale->group;
                                   if($Total >= 4)
                                   {
                                        $totalincentive= $incentive;
                                        $tds=$totalincentive * 0.05;
                                        $final=$totalincentive - $tds;
                                        $agentParent->update([
                                            'designation'=>'MANAGER'
                                        ]);
                                        $agentPDG->update([
                                            'designation'=>"MANAGER",
                                            'incentive' =>  $totalincentive,
                                            'tds_deduction' => $tds,
                                            'final_incentive' => $final
                                        ]);
                                        break;
                                    }
                                }
                            }
                        }
                        else if($agentPDG->designation === 'MANAGER')
                        {
                            if($agentPDG->direct >= 6  && $agentPDG->group >= 36 )
                            {
                                $downAgent=AgentLevels::where('parent_id',$agentParent->id)->get();

                                foreach ($downAgent as $dagent) 
                                {
                                    // Access each agent's properties
                                   $agentID=$dagent->agent_id;
                                   $DGsale=AgentDGSale::where('agent_id',$agentID)->first();
                                   $Total=$DGsale->direct + $DGsale->group;
                                   if($Total >= 18)
                                   {
                                        $DAgent=AgentLevels::where('parent_id',$agent->id)->get();
                                        $incentive=0;
                                        foreach($DAgent as $DA)
                                        {
                                            $DAid=$DA->agent_id;
                                            $DGS=AgentDGSale::where('agent_id',$DAid)->first();
                                            $DGtotal=$DGS->direct + $DGS->group;
                                            if($DGS->designation === 'MANAGER')
                                            {
                                                $incentive=$incentive + ($DGtotal * 3000);
                                            }
                                            else if($DGS->designation === 'ASSOCIATE')
                                            {
                                                $incentive=$incentive + ($DGtotal * (3000 + 5000));
                                            }
                                        }

                                        $totalincentive=$incentive;
                                        $tds=$totalincentive * 0.05;
                                        $final=$totalincentive - $tds;
                                        $agentParent->update([
                                            'designation'=>'SM'
                                        ]);
                                        $agentPDG->update([
                                            'designation'=>"SM",
                                            'incentive' =>  $totalincentive,
                                            'tds_deduction' => $tds,
                                            'final_incentive' => $final
                                        ]);
                                      break;
                                   } 
                                }
                            }
                        }
                        else if($agentPDG->designation === 'SM')
                        {
                            $downLevel=DB::table('agent_levels')
                            ->leftJoin('agent_registers','agent_levels.agent_id','=','agent_registers.id')
                            ->select(
                                'agent_registers.designation'
                            )->where('agent_levels.referral', 'like', '%' . $agentParent->referral_code . '%')
                            ->where('agent_registers.designation','MANAGER')->count();
                            
                           if($downLevel >= 2)
                           {
                               if($agentPDG->direct >= 11  && $agentPDG->group >= 114 )
                                {
                                    $downAgent=AgentLevels::where('parent_id',$agentParent->id)->get();

                                    foreach ($downAgent as $dagent) 
                                    {
                                        // Access each agent's properties
                                       $agentID=$dagent->agent_id;
                                       $DGsale=AgentDGSale::where('agent_id',$agentID)->first();
                                       $Total=$DGsale->direct + $DGsale->group;
                                       if($Total >= 57)
                                       {
                                            $DAgent=AgentLevels::where('parent_id',$agent->id)->get();
                                            $incentive=0;
                                            foreach($DAgent as $DA)
                                            {
                                            $DAid=$DA->agent_id;
                                            $DGS=AgentDGSale::where('agent_id',$DAid)->first();
                                            $DGtotal=$DGS->direct + $DGS->group;
                                            if($DGS->destination === 'SM')
                                            {
                                                $incentive=$incentive + ($DGtotal * 2000);
                                            }
                                            else if($DGS->designation === 'MANAGER')
                                            {
                                                $incentive=$incentive + ($DGtotal * (3000+2000));
                                            }
                                            else if($DGS->designation === 'ASSOCIATE')
                                            {
                                                $incentive=$incentive + ($DGtotal * (5000+3000+2000));
                                            }
                                            }
            

                                            $totalincentive= $incentive;
                                            $tds=$totalincentive * 0.05;
                                            $final=$totalincentive - $tds;
                                            $agent->update([
                                                'designation'=>'AGM'
                                            ]);
                                            $agentDG->update([
                                                'designation'=>"AGM",
                                                'incentive' =>  $totalincentive,
                                                'tds_deduction' => $tds,
                                                'final_incentive' => $final
                                            ]);
                                            break;
                                       }
                                    }
                                }
                            }  
                        }
                        else if($agentPDG->designation === 'AGM')
                        {
                            $downLevel=DB::table('agent_levels')
                            ->leftJoin('agent_registers','agent_levels.agent_id','=','agent_registers.id')
                            ->select(
                                DB::raw('COUNT(CASE WHEN agent_registers.designation = "AGM" THEN 1 END) as AGM_count'),
                                DB::raw('COUNT(CASE WHEN agent_registers.designation = "SM" THEN 1 END) as sm_count')
                            )->where('agent_levels.referral', 'like', '%' . $agentParent->referral_code . '%')->first(); 
                                        
                           if($downLevel->AGM_count >= 1 && $downLevel->sm_count >=2 )
                           {
                                if($agentPDG->direct >= 17  && $agentPDG->group >= 282 )
                                {
                                    $downAgent=AgentLevels::where('parent_id',$agentParent->id)->get();

                                    foreach ($downAgent as $dagent) 
                                    {
                                        // Access each agent's properties
                                        $agentID=$dagent->agent_id;
                                        $DGsale=AgentDGSale::where('agent_id',$agentID)->first();
                                        $Total=$DGsale->direct + $DGsale->group;
                                        if($Total >= 141)
                                        {
                                            $DAgent=AgentLevels::where('parent_id',$agent->id)->get();
                                            $incentive=0;
                                            foreach($DAgent as $DA)
                                            {
                                                $DAid=$DA->agent_id;
                                                $DGS=AgentDGSale::where('agent_id',$DAid)->first();
                                                $DGtotal=$DGS->direct + $DGS->group;
                                                if($DGS->designation === 'AGM'){
                                                    $incentive=$incentive + ($DGtotal * 1500);
                                                }
                                                else if($DGS->designation === 'SM')
                                                {
                                                    $incentive = $incentive + ($DGtotal * (1500 + 2000));
                                                }
                                                else if($DGS->designation === 'MANAGER')
                                                {
                                                    $incentive=$incentive + ($DGtotal * (1500 + 2000 + 3000));
                                                }
                                                else if($DGS->designation === 'ASSOCIATE')
                                                {
                                                    $incentive=$incentive + ($DGtotal * (1500 + 2000 + 3000 + 5000));
                                                }
                                            }
                                        
                                            $totalincentive= $incentive;
                                            $tds=$totalincentive * 0.05;
                                            $final=$totalincentive - $tds;
                                            $agent->update([
                                                'designation'=>'GM'
                                            ]);
                                            $agentDG->update([
                                                'designation'=>"GM",
                                                'incentive' =>  $totalincentive,
                                                'tds_deduction' => $tds,
                                                'final_incentive' => $final,
                                                'salary' => 40000,
                                            ]);
                                            break;
                                                }
                                    } 
                                }
                            }
                        } 
                        else if($agentPDG->designation === 'GM')
                        {
                            $downLevel=DB::table('agent_levels')
                            ->leftJoin('agent_registers','agent_levels.agent_id','=','agent_registers.id')
                            ->select(
                                DB::raw('COUNT(CASE WHEN agent_registers.designation = "AGM" THEN 1 END) as AGM_count'),
                                DB::raw('COUNT(CASE WHEN agent_registers.designation = "SM" THEN 1 END) as sm_count')
                            )->where('agent_levels.referral', 'like', '%' . $agentParent->referral_code . '%')->first(); 
    
                            if($downLevel->AGM_count >= 2 && $downLevel->sm_count >=4 )
                            {
                                if($agentPDG->direct >= 24  && $agentPDG->group >= 600 )
                                {
                                    $downAgent=AgentLevels::where('parent_id',$agentParent->id)->get();

                                    foreach ($downAgent as $dagent) 
                                    {
                                        // Access each agent's properties
                                        $agentID=$dagent->agent_id;
                                        $DGsale=AgentDGSale::where('agent_id',$agentID)->first();
                                        $Total=$DGsale->direct + $DGsale->group;
                                        if($Total >= 300)
                                            { 
                                                $DAgent=AgentLevels::where('parent_id',$agent->id)->get();
                                                $incentive=0;
                                                foreach($DAgent as $DA)
                                                {
                                                    $DAid=$DA->agent_id;
                                                    $DGS=AgentDGSale::where('agent_id',$DAid)->first();
                                                    $DGtotal=$DGS->direct + $DGS->group;

                                                    if($DGS->designation === 'GM')
                                                    {
                                                        $incentive = $incentive + ($DGtotal * 1000);
                                                    }
                                                    if($DGS->designation === 'AGM'){
                                                        $incentive=$incentive + ($DGtotal * (1000 + 1500));
                                                    }
                                                    else if($DGS->designation === 'SM')
                                                    {
                                                        $incentive = $incentive + ($DGtotal * (1000 + 1500 + 2000));
                                                    }
                                                    else if($DGS->designation === 'MANAGER')
                                                    {
                                                        $incentive=$incentive + ($DGtotal * (1000 + 1500 + 2000 + 3000));
                                                    }
                                                    else if($DGS->designation === 'ASSOCIATE')
                                                    {
                                                        $incentive=$incentive + ($DGtotal * (1000 + 1500 + 2000 + 3000 + 5000));
                                                    }
                                                }
            
                                                $totalincentive= $incentive;
                                                $tds=$totalincentive * 0.05;
                                                $final=$totalincentive - $tds;
                                                $agent->update([
                                                    'designation'=>'SGM'
                                                ]);
                                                $agentDG->update([
                                                    'designation'=>"SGM",
                                                    'incentive' =>  $totalincentive,
                                                    'tds_deduction' => $tds,
                                                    'final_incentive' => $final,
                                                    'salary' => 70000,
                                                ]);
                                                break;
                                            }
                                    }
                                }
                            }
                        }    
                        $agentLevel = $parentLevel;
                    }
                    
                    if($CheckIncome->isEmpty())
                    {
                        $total_amount = $plot_sale->totalAmount; 

                        $incomePercentages = [
                            "1" => 8, "2" => 3, "3" => 2,"4" => 1,"5" => 1,"6" => 0.70,"7" => 0.60,"8" => 0.40,"9" => 0.20,"10" => 0.10
                        ];

                        $index=1;

                        if($plot_sale->buying_type === 'EMI')
                        {
                            $base=$plot->plot_area * 11500;
                            $diff=$total_amount - $base;
                        }
                        else if($plot_sale->buying_type === 'CASH')
                        {
                            $base=$plot->plot_area * 9500;
                            $diff=$total_amount - $base;                            
                        }
                    
                        $diffHalf=$diff/2;
                        // $levelTables=AgentLevels::get()
                        
                        if($percentagePaid >= 30 && $percentagePaid < 50)
                        {
                            $diffIncome=$diffHalf/2;
                            $agentIncome=$base/2;
                        }
                        else if($percentagePaid >= 50)
                        {
                            $diffIncome=$diffHalf;
                            $agentIncome=$base;
                        }

                        $MVG=AgentRegister::where('referral_code',"0")->first();

                        AgentIncome::create([
                            'plot_sale_id' => $plot_sale->id, 
                            'income_type' => 'CORPUS',
                            'final_agent' => $MVG->id,
                            'total_income' => $diffIncome,
                            'tds_deduction' => $diffIncome * 0.05,  // Assuming a TDS of 5%
                            'final_income' => $diffIncome - ($diffIncome * 0.05),
                            'pancard_status' => 0 ,  // Assuming you have this value

                        ]);

                        AgentIncome::create([
                            'plot_sale_id' => $plot_sale->id, 
                            'income_type' => 'CORPUS',
                            'final_agent' => $plot_sale->agent_id,
                            'total_income' => $diffIncome,
                            'tds_deduction' => $diffIncome * 0.05,  // Assuming a TDS of 5%
                            'final_income' => $diffIncome - ($diffIncome * 0.05),
                            'pancard_status' => 1 ,  // Assuming you have this value
                        ]);

                        // return response()->json([$diffHalf]);

                        while ($Level) 
                        {
                            $total_income = ($incomePercentages["{$index}"] / 100) * $agentIncome ;
                            $pancard= AgentRegister::find($Level->agent_id);
                            AgentIncome::create([
                                'plot_sale_id' => $plot_sale->id, 
                                'income_type' => 'DISTRIBUTED',
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
                                $index++;
                                if($index === 11)
                                {
                                    break;
                                }
                            } else {                                
                                // Slice the array from the start up to the given level
                                $selectedPercentages = array_slice($incomePercentages, 0, $index, true);
                                
                                // Calculate the sum of the selected values
                                $remainingPercent = array_sum($selectedPercentages);
                                
                                $mvgCommision= 17 - $remainingPercent;
                                
                                $mvg_income = ($mvgCommision / 100) * $agentIncome;
                                
                                AgentIncome::create([
                                    'plot_sale_id'  => $plot_sale->id, 
                                    'income_type' => 'DISTRIBUTED',
                                    'final_agent'   => $parentID,
                                    'total_income'  => $mvg_income,
                                    'tds_deduction' => $mvg_income * 0.05,  // Assuming a TDS of 5%
                                    'final_income'  => $mvg_income - ($mvg_income * 0.05),
                                    'pancard_status' => 0,  // Assuming you have this value
                                ]);
                                break;
                            }
                        }
                    } 
                       
                    $plot_sale->update([
                        'TDG_status' => 1,
                    ]);
                }
                else if($status === 'BOOKED' && $plot_sale->TDG_status === 1)
                {
                    if($VALUE >= 50)
                    {
                        exit;
                    }
                    else if($VALUE < 50)
                    {
                        foreach($CheckIncome as $income)
                        {
                            $income->update([
                            'total_income' => $income->total_income * 2,                            
                            'tds_deduction' => $income->tds_deduction * 2,
                            'final_income' => $income->final_income * 2,                        
                            ]
                        );
                            
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

    public function AgentDGsale()
    {
        $user= Auth::guard('sanctum')->user();

        $dgsale=AgentDGSale::where('agent_id',$user->id)->first();

        if(!$dgsale)
        {
            return response()->json(['success'=>0,'error'=>'No Direct or Group Sale Exist'],404);            
        }

        if($dgsale->designation === "ASSOCIATE")
        {
            $directUp = 2;
            $groupUp = 8;         
            $promotion = "MANAGER";
        }
        else if($dgsale->designation === "MANAGER")
        {
            $directUp = 6;
            $groupUp = 36;         
            $promotion = "Senior Manager";
        }
        else if($dgsale->designation === "SM")
        {
            $directUp = 11;
            $groupUp = 114;         
            $promotion = "Assistant General Manager";
        }
        else if($dgsale->designation === "AGM")
        {
            $directUp = 17;
            $groupUp = 282;         
            $promotion = "General Manager";
        }
        else if($dgsale->designation === "GM")
        {
            $directUp = 24;
            $groupUp = 600;         
            $promotion = "Senior General Manager";
        }


        return response()->json(
            [
                'success'=>1,
                'DGSale'=>
                [
                    "id" => $dgsale->id,
                    "agent_id" => $dgsale->agent_id,
                    "direct" => $dgsale->direct,
                    "group" => $dgsale->group,
                    "designation" => $dgsale->designation,
                    "incentive" => $dgsale->incentive,
                    "tds_deduction" => $dgsale->tds_deduction,
                    "final_incentive" => $dgsale->final_incentive,
                    "salary" => $dgsale->salary === 0 ? null : $dgsale->salary,
                    "directUp"=> $directUp ,          
                    "groupUp" => $groupUp ,          
                    "promotion" =>  $promotion,
                ]
            ],200);
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
