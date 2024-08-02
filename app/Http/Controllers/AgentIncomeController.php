<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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
    public function agentIncome(Request $request)
    {

        $params=$request->query('agent_id');

        $income = DB::table('agent_incomes')
        ->leftJoin('plot_sales', 'agent_incomes.plot_sale_id', '=', 'plot_sales.id')
        ->leftJoin('plots', 'plot_sales.plot_id', '=', 'plots.id')
        ->select(
                 'plot_sales.plot_id',
                 'plots.plot_No',
                 'plots.plot_type',
                    'plot_sales.totalAmount',
                    'agent_incomes.total_income',
                    'agent_incomes.tds_deduction',
                    'agent_incomes.final_income',
                    'agent_incomes.transaction_status',
                )->where('agent_incomes.final_agent',$params)->get();

        if($income->isEmpty())
        {
            return response()->json(['success' => 0,'error' =>"Data don't Exist"],400);
        }

                return response()->json(['success'=>1,'Incomes'=>$income],200);
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

        $sales = DB::table('plot_sales')
        ->leftJoin('agent_registers', 'plot_sales.agent_id', '=', 'agent_registers.id')
        ->select(
            DB::raw('YEAR(plot_sales.created_at) as year'),
            DB::raw('MONTH(plot_sales.created_at) as month'),
            DB::raw('SUM(plot_sales.totalAmount) as monthSale')
        )
        ->where('agent_registers.id', $user->id)
        ->groupBy(DB::raw('YEAR(plot_sales.created_at)'))
        ->groupBy(DB::raw('MONTH(plot_sales.created_at)'))
        ->orderBy(DB::raw('YEAR(plot_sales.created_at)'))
        ->orderBy(DB::raw('MONTH(plot_sales.created_at)'))
        ->get();

        if($sales->isEmpty())
        {
            return response()->json(['success' => 0,'error' =>"Data don't Exist"],400);
        }
        
        $total=$sales->sum('monthSale');

        $sales->transform(function ($item) use ($monthNames) {
            $item->month_name = $monthNames[$item->month] ?? 'Unknown';
            return $item;
        });

        return response()->json(['success'=>1,'totalSale'=>$total,'Sales'=>$sales],200);
    }

}
