<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AgentDGSale;
use App\Models\AgentIncome;
use App\Models\Plot_Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class AgentIncomeController extends Controller
{

     /**
     * @group Agent Income
     *      
     * Get DISTRIBUTED income for Agent.
     * This api shows All the Distrubuted income of agent currently Logged in
     * 
     * @authenticated
     *
     * @response 200 {
     *   "success": 1,
     *   "Incomes": [
     *     {
     *       "plot_id": 1,
     *       "plot_No": "P-101",
     *       "plot_type": "Residential",
     *       "totalAmount": 500000,
     *       "income_type": "DISTRIBUTED",
     *       "total_income": 5000,
     *       "tds_deduction": 500,
     *       "final_income": 4500,
     *       "transaction_status": "COMPLETED",
     *       "income_DG": "direct"
     *     }
     *   ]
     * }
     *
     * @response 404 {
     *   "success": 0,
     *   "error": "Data don't Exist or Data Not Found"
     * }
     */

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


    /**
     * @group Agent Income
     * 
     * Get CORPUS income for the agent.
     * This api shows All the CORPUS income of agent currently Logged in
     * 
     * @authenticated
     *
     * @response 200 {
     *   "success": 1,
     *   "Incomes": [
     *     {
     *       "plot_id": 1,
     *       "plot_No": "P-101",
     *       "plot_type": "Residential",
     *       "totalAmount": 500000,
     *       "income_type": "CORPUS",
     *       "total_income": 10000,
     *       "tds_deduction": 1000,
     *       "final_income": 9000,
     *       "transaction_status": "COMPLETED",
     *       "income_DG": "group"
     *     }
     *   ]
     * }
     *
     * @response 404 {
     *   "success": 0,
     *   "error": "Data don't Exist or Data Not Found"
     * }
     */
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

    /**
     * Get both CORPUS and DISTRIBUTED income for the agent.
     * This API show the CORPUS and DISTRIBUTED status regarding the PLOT sold by either him or
     * Agent in his group 
     * 
     * @group Agent Income
     * @authenticated
     *
     * @response 200 {
     *   "success": 1,
     *   "Incomes": [
     *     {
     *       "Plot_ID": 1,
     *       "plot_No": "P-101",
     *       "Plot_Sale_ID": 1,
     *       "income_CORPUS": 1,
     *       "income_DISTRIBUTED": 1
     *     }
     *   ]
     * }
     */
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

     /**
     * Get Agents Income (Admin view).
     * This API Provide the Single Agent All Income Data if ID is provide else it 
     * will give all Agents Income data  
     * 
     * 
     * @group Admin Income
     *
     * @queryParam id integer The ID of the agent to filter income by.
     *
     * @response 200 {
     *   "success": 1,
     *   "Incomes": [
     *     {
     *       "id": 1,
     *       "final_agent": 2,
     *       "fullname": "John Doe",
     *       "plot_id": 1,
     *       "plot_No": "P-101",
     *       "plot_type": "Residential",
     *       "totalAmount": 500000,
     *       "income_type": "DISTRIBUTED",
     *       "total_income": 5000,
     *       "tds_deduction": 500,
     *       "final_income": 4500,
     *       "transaction_status": "COMPLETED",
     *       "income_DG": "direct"
     *     }
     *   ]
     * }
     *
     * @response 404 {
     *   "success": 0,
     *   "error": "Data don't Exist or Data Not Found"
     * }
     */
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

    /**
     * @group Agent Income
     *
     * Retrieve Super Agent Income Data
     *
     * This endpoint retrieves the income details for a agent. If an `id` is provided as a query parameter, it fetches the income data for the specific agent. Otherwise, it fetches income data for all agents.
     *
     * @queryParam id integer The ID of the agent to filter the results. Example: 1
     *
     * @response 200 {
     *   "success": 1,
     *   "Incomes": [
     *     {
     *       "id": 1,
     *       "final_agent": 123,
     *       "fullname": "John Doe",
     *       "plot_id": 45,
     *       "plot_No": "P-120",
     *       "plot_type": "Residential",
     *       "totalAmount": 50000,
     *       "income_type": "Commission",
     *       "total_income": 1500,
     *       "tds_deduction": 150,
     *       "final_income": 1350,
     *       "transaction_status": "COMPLETED",
     *       "income_DG": "direct"
     *     }
     *   ]
     * }
     *
     * @response 404 {
     *   "success": 0,
     *   "error": "Data don't Exist or Data Not Found"
     * }
     */

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


    /**
     * @group Agent Income
     *
     * Retrieve Agent DG Sale Data
     *
     * This endpoint retrieves the direct and group sales data for agents whose designation is not 'ASSOCIATE'.
     *
     * @response 200 {
     *   "success": 1,
     *   "agentDG": [
     *     {
     *       "fullname": "John Doe",
     *       "referral_code": "REF123",
     *       "direct": 10,
     *       "group": 5,
     *       "designation": "SENIOR AGENT",
     *       "incentive": 1000,
     *       "tds_deduction": 100,
     *       "final_incentive": 900,
     *       "salary": 50000,
     *       "TransactionStatus": "COMPLETED"
     *     }
     *   ]
     * }
     *
     * @response 404 {
     *   "success": 0,
     *   "error": "Data don't Exist or Data Not Found"
     * }
     */

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

    /**
     * @group Agent Income
     *
     * Update Agent DG Sale Transaction Status By Admin
     *
     * This endpoint updates the transaction status of an Agent's DG sale to "COMPLETED".
     *
     * @queryParam id integer required The ID of the Agent's DG sale to update. Example: 1
     *
     * @response 201 {
     *   "success": 1,
     *   "message": "Transaction Status updated",
     *   "agent_transaction": {
     *     "id": 1,
     *     "transactionStatus": "COMPLETED"
     *   }
     * }
     *
     * @response 404 {
     *   "success": 0,
     *   "message": "No Agent Transaction Found"
     * }
     *
     * @response 500 {
     *   "success": 0,
     *   "error": "Internal Server Error. Error message"
     * }
     */

    public function UpdateAgentDGTransaction(Request $request)
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

    /**
     * @group Agent Income
     *
     * Update Agent Income Transaction Status By Admin
     *
     * This endpoint updates the transaction status of an Agent Income to "PARTIALLY COMPLETED" or "COMPLETED" based on amount
     * Paid by Client.
     *
     * @queryParam id integer required The ID of the Agent's DG sale to update. Example: 1
     *
     * @response 201 {
     *   "success": 1,
     *   "message": "Transaction Status updated",
     *   "agentTransaction": {
     *     "id": 1,
     *     "transactionStatus": "COMPLETED"
     *   }
     * }
     *
     * @response 404 {
     *   "success": 0,
     *   "message": "No Agent Transaction Found"
     * }
     *
     * @response 500 {
     *   "success": 0,
     *   "error": "Internal Server Error. Error message"
     * }
     */

    public function UpdateAgentIncomeTransaction(Request $request)
    {
        try {
            //code...
            $params=$request->query('id');
            
            $agentTransaction=AgentIncome::where('id',$params)->first();

            $PlotSale=Plot_Sale::where('id',$agentTransaction->plot_sale_id)->first();

            // return response()->json($PlotSale);
    
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
            else if($PlotSale->plot_value < 30) {
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



    /**
     * @group Agent Income
     *
     * Agent Total Sale 
     *
     * This endpoint gives Agent total Sales of every month and Years .
     *
     * @authenticated
     *
     * @response 201 {
     *   "success": 1,
     *   "totalSale"=>sum of every month sale,
     *   "Sales"=>[
     *              
     *             ]
     * }
     *
     * @response 404 {
     *   "success": 0,
     *   "message": "Data don't Exist or Data Not Found"
     * }
     *
     */

    public function AgentSalesYearMonth(Request $request)
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

    public function DailyTransactionAgent(Request $request)
    {
        $today = Carbon::today()->toDateString();
        
        $date= $request->query('date');

        $Agent = DB::table('agent_incomes')
        ->leftJoin('agent_registers','agent_incomes.final_agent','=','agent_registers.id')
        ->leftJoin('plot_sales', 'agent_incomes.plot_sale_id', '=', 'plot_sales.id')
        ->select(
            'agent_incomes.final_agent',
            'agent_registers.fullname',
            'agent_registers.contact_no',
            'agent_incomes.income_type',
            'agent_incomes.transaction_status',
            DB::raw("
            ROUND(CASE
                WHEN agent_incomes.transaction_status = 'PARTIALLY COMPLETED' THEN agent_incomes.final_income
                WHEN agent_incomes.transaction_status = 'COMPLETED' THEN agent_incomes.final_income / 2
            END) as transaction"),
            'agent_incomes.plot_sale_id',
            'plot_sales.plot_value',
            DB::raw('DATE(agent_incomes.updated_at) as transaction_date')
        )
        ->where('agent_incomes.transaction_status', '!=', 'PENDING')
        ->where('agent_registers.id', "!=",'24')
        ->whereDate('agent_incomes.updated_at', $date? $date :$today)
        ->get();

    
        if($Agent->isEmpty())
        {
            return response()->json(
            [
                'success'=>0,
                'message'=> "No Transaction Found Regarding this Agent for this particular date"
            ],200);   
        }
        return response()->json(
        [
            'success'=>1,
            'data'=>$Agent
        ],200);
    
    }

}
