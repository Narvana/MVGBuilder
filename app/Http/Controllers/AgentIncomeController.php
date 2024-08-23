<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AgentDGSale;
use App\Models\AgentIncome;
use App\Models\Plot_Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class AgentIncomeController extends Controller
{
    //
    // public function createIncome()
    public function agentIncomeDISTRIBUTED(Request $request)
    {

        $params=Auth::guard('sanctum')->user();

        $income = DB::table('agent_incomes')
        ->leftJoin('plot_sales', 'agent_incomes.plot_sale_id', '=', 'plot_sales.id')
        ->leftJoin('plots', 'plot_sales.plot_id', '=', 'plots.id')
        ->select(
                 'plot_sales.plot_id',
                 'plots.plot_No',
                 'plots.plot_type',
                 'plot_sales.totalAmount',
                 'agent_incomes.income_type',
                 'agent_incomes.total_income',
                 'agent_incomes.tds_deduction',
                 'agent_incomes.final_income',
                 'agent_incomes.transaction_status',
                 DB::raw('CASE 
                 WHEN agent_incomes.final_agent = plot_sales.agent_id THEN "direct"
                    ELSE "group"
                 END AS income_DG')
                )
                ->where('agent_incomes.final_agent',$params->id)
                ->where('agent_incomes.income_type', '=' , 'DISTRIBUTED')
                ->get();

        if($income->isEmpty())
        {
            return response()->json(['success' => 0,'error' =>"Data don't Exist or Data Not Found"],404);
        }

                return response()->json(['success'=>1,'Incomes'=>$income],200);
    }

    public function agentIncomeCORPUS(Request $request)
    {

        $params=Auth::guard('sanctum')->user();

        $income = DB::table('agent_incomes')
        ->leftJoin('plot_sales', 'agent_incomes.plot_sale_id', '=', 'plot_sales.id')
        ->leftJoin('plots', 'plot_sales.plot_id', '=', 'plots.id')
        ->select(
                 'plot_sales.plot_id',
                 'plots.plot_No',
                 'plots.plot_type',
                 'plot_sales.totalAmount',
                 'agent_incomes.income_type',
                 'agent_incomes.total_income',
                 'agent_incomes.tds_deduction',
                 'agent_incomes.final_income',
                 'agent_incomes.transaction_status',
                 DB::raw('CASE 
                 WHEN agent_incomes.final_agent = plot_sales.agent_id THEN "direct"
                    ELSE "group"
                 END AS income_DG')
                )
                ->where('agent_incomes.final_agent',$params->id)
                ->where('agent_incomes.total_income','!=', "0")
                ->where('agent_incomes.income_type', '=' , 'CORPUS')
                ->get();

        if($income->isEmpty())
        {
            return response()->json(['success' => 0,'error' =>"Data don't Exist or Data Not Found"],404);
        }

                return response()->json(['success'=>1,'Incomes'=>$income],200);
    }

    public function agentIncomeThird(Request $request)
    {
        $params=Auth::guard('sanctum')->user();

        $income=DB::table('agent_incomes')
        ->leftJoin('agent_registers','agent_incomes.final_agent','=','agent_registers.id')
        ->leftJoin('agent_d_g_sales','agent_incomes.final_agent','=','agent_d_g_sales.agent_id')
        ->leftJoin('plot_sales','agent_incomes.plot_sale_id','=','plot_sales.id')
        ->leftJoin('plots','plot_sales.plot_id','=','plots.id')
        ->select(
            'plots.id as Plot_ID',
            'plots.plot_No',
            'plot_sales.id as Plot_Sale_ID',
            DB::raw('MAX(CASE WHEN agent_incomes.income_type = "CORPUS" AND agent_incomes.total_income > 0 THEN 1 ELSE 0 END) as income_CORPUS'),
            DB::raw('MAX(CASE WHEN agent_incomes.income_type = "DISTRIBUTED" AND agent_incomes.total_income > 0 THEN 1 ELSE 0 END) as income_DISTRIBUTED')
        )
        ->where('agent_incomes.final_agent', $params->id)
        ->groupBy('plots.id', 'plots.plot_No', 'plot_sales.id')
        ->get();
        return response()->json(['success'=>1,'Incomes'=>$income],200);

    } 


    public function agentIncomeAdmin(Request $request)
    {
        $params=$request->query('id');

        $income = DB::table('agent_incomes')
        ->leftJoin('plot_sales', 'agent_incomes.plot_sale_id', '=', 'plot_sales.id')
        ->leftJoin('plots', 'plot_sales.plot_id', '=', 'plots.id')
        ->leftJoin('agent_registers','agent_incomes.final_agent','=','agent_registers.id')
        ->select(
                 'agent_incomes.id',
                 'agent_incomes.final_agent',
                 'agent_registers.fullname',
                 'plot_sales.plot_id',
                 'plots.plot_No',
                 'plots.plot_type',
                 'plot_sales.totalAmount',
                 'agent_incomes.income_type',
                 'agent_incomes.total_income',
                 'agent_incomes.tds_deduction',
                 'agent_incomes.final_income',
                 'agent_incomes.transaction_status',
                 DB::raw('CASE 
                 WHEN agent_incomes.final_agent = plot_sales.agent_id THEN "direct"
                    ELSE "group"
                 END AS income_DG')
                 )
                 ->where('agent_incomes.total_income','!=', "0")
                 ->where('agent_registers.referral_code','!=', "0");
               
        if($params){
            $agentIncome=$income->where('agent_incomes.final_agent',$params)->get();
            
            if($agentIncome->isEmpty())
            {
                return response()->json(['success' => 0,'error' =>"Data don't Exist or Data Not Found"],404);
            }
            return response()->json(['success'=>1,'Incomes'=>$agentIncome],200);
        }else{
            $agentIncome=$income->get();
            
        if($agentIncome->isEmpty())
        {
            return response()->json(['success' => 0,'error' =>"Data don't Exist or Data Not Found"],404);
        }
 
            return response()->json(['success'=>1,'Incomes'=>$agentIncome],200);
        }
    }

    public function superAgentIncomeAdmin(Request $request)
    {
        $params=$request->query('id');

        $income = DB::table('agent_incomes')
        ->leftJoin('plot_sales', 'agent_incomes.plot_sale_id', '=', 'plot_sales.id')
        ->leftJoin('plots', 'plot_sales.plot_id', '=', 'plots.id')
        ->leftJoin('agent_registers','agent_incomes.final_agent','=','agent_registers.id')
        ->select(
                 'agent_incomes.id',
                 'agent_incomes.final_agent',
                 'agent_registers.fullname',
                 'plot_sales.plot_id',
                 'plots.plot_No',
                 'plots.plot_type',
                 'plot_sales.totalAmount',
                 'agent_incomes.income_type',
                 'agent_incomes.total_income',
                 'agent_incomes.tds_deduction',
                 'agent_incomes.final_income',
                 'agent_incomes.transaction_status',
                 DB::raw('CASE 
                 WHEN agent_incomes.final_agent = plot_sales.agent_id THEN "direct"
                    ELSE "group"
                 END AS income_DG')
                 )
                 ->where('agent_incomes.total_income','!=', "0")
                 ->where('agent_registers.referral_code','=', "0");
               
        if($params){
            $agentIncome=$income->where('agent_incomes.final_agent',$params)->get();

            if($agentIncome->isEmpty())
            {
                return response()->json(['success' => 0,'error' =>"Data don't Exist or Data Not Found"],404);
            }
            return response()->json(['success'=>1,'Incomes'=>$agentIncome],200);
        }else{
            $agentIncome=$income->get();
            
            if($agentIncome->isEmpty())
            {
                return response()->json(['success' => 0,'error' =>"Data don't Exist or Data Not Found"],404);
            }
    
            return response()->json(['success'=>1,'Incomes'=>$agentIncome],200);
        }
    }

    public function AdminAgentDGSale(Request $request)
    {
        $agentDGAdmin = DB::table('agent_d_g_sales')
        ->leftJoin('agent_registers','agent_d_g_sales.agent_id','=','agent_registers.id')
        ->select(
            'agent_registers.fullname',
            'agent_registers.referral_code',
            'agent_d_g_sales.direct',
            'agent_d_g_sales.group',
            'agent_d_g_sales.designation',
            'agent_d_g_sales.incentive',
            'agent_d_g_sales.tds_deduction',
            'agent_d_g_sales.final_incentive',
            'agent_d_g_sales.salary',
            'agent_d_g_sales.TransactionStatus',
        )->where('agent_d_g_sales.designation', '!=' ,'ASSOCIATE')->get();

        if($agentDGAdmin->isEmpty())
        {
            return response()->json(['success' => 0,'error' =>"Data don't Exist or Data Not Found"],404);
        }
        return response()->json(['success'=>1,'agentDG'=>$agentDGAdmin],200);
    }

    public function UpdateAgentAdminTransaction(Request $request)
    {
        try {
            //code...
            $params=$request->query('id');
            $agentTransaction=AgentDGSale::where('id',$params)->first();
    
            if(!$agentTransaction)
            {
                return response()->json([
                    'success'=>0,
                    'message' => 'No Agent Transaction Found',
                ], 404);
            }

            $Status = "COMPLETED";

            $agentTransaction->transactionStatus=$Status;

            $agentTransaction->save();
  
            return response()->json([
                'success'=>1,
                'message' => 'Transaction Status updated',
                'agent_transaction' => $agentTransaction
            ], 201);    

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => 0,
                'error' => 'Internal Server Error. ' . $th->getMessage()
            ], 500);
        }
    }

    public function UpdateAgentTransaction(Request $request)
    {
        try {
            //code...
            $params=$request->query('id');
            $agentTransaction=AgentIncome::where('id',$params)->first();
    
            $PlotSale=Plot_Sale::where('id',$agentTransaction->plot_sale_id)->first();
    
            if(!$agentTransaction)
            {
                return response()->json([
                    'success'=>0,
                    'message' => 'No Agent Transaction Found',
                ], 404);
            }
            
            if ($PlotSale->plot_value >= 30 && $PlotSale->plot_value < 50) {
                $transactionStatus = "PARTIALLY COMPLETED";
            } else if ($PlotSale->plot_value >= 50) {
                $transactionStatus = "COMPLETED";
            } 
            else {
                $transactionStatus = "PENDING";
            }
        
            // Update the transaction status
            $agentTransaction->transaction_status = $transactionStatus;
            $agentTransaction->save();
    
            return response()->json([
                'success'=>1,
                'message' => 'Transaction Status Updated',
                'agent_transaction' => $agentTransaction 
            ], 201);    

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'success' => 0,
                'error' => 'Internal Server Error. ' . $th->getMessage()
            ], 500);
        }
    }

    public function agentSales(Request $request)
    {
        $user=Auth::guard('sanctum')->user();

        $monthNames = [
            1 => 'JAN',
            2 => 'FEB',
            3 => 'MAR',
            4 => 'APR',
            5 => 'MAY',
            6 => 'JUN',
            7 => 'JUL',
            8 => 'AUG',
            9 => 'SEP',
            10 => 'OCT',
            11 => 'NOV',
            12 => 'DEC',
        ];

        $sales = DB::table('agent_incomes')
        ->leftJoin('agent_registers', 'agent_incomes.final_agent', '=', 'agent_registers.id')
        ->select(
            DB::raw('YEAR(agent_incomes.created_at) as year'),
            DB::raw('MONTH(agent_incomes.created_at) as month'),
            DB::raw('SUM(agent_incomes.final_income) as monthSale')
        )
        ->where('agent_registers.id', $user->id)
        ->groupBy(DB::raw('YEAR(agent_incomes.created_at)'))
        ->groupBy(DB::raw('MONTH(agent_incomes.created_at)'))
        ->orderBy(DB::raw('YEAR(agent_incomes.created_at)'))
        ->orderBy(DB::raw('MONTH(agent_incomes.created_at)'))
        ->get();

        if($sales->isEmpty())
        {
            return response()->json(['success' => 0,'error' =>"Data don't Exist or Data Not Found"],404);
        }
        
        $total=$sales->sum('monthSale');

        $sales->transform(function ($item) use ($monthNames) {
            $item->month_name = $monthNames[$item->month] ?? 'Unknown';
            return $item;
        });

        return response()->json(['success'=>1,'totalSale'=>$total,'Sales'=>$sales],200);
    }

}
