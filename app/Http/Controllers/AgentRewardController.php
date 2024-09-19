<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\AgentDGSale;
use App\Models\AgentReward;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class AgentRewardController extends Controller
{
        // Agent_id,
    // Direct
    // Group
    // Reward_Achieved
    // Received
    // Next_Reward
    public function showAgentDown(Request $request)
    {
        //  UPDATED CODE
        $user=Auth::guard('sanctum')->user();
        
        $level1Agents=DB::table('agent_levels')
                      ->leftJoin('agent_registers','agent_levels.agent_id','=','agent_registers.id')
                      ->select(
                        "agent_levels.level",
                        "agent_registers.id",
                        "agent_registers.referral_code",
                        "agent_registers.pancard_no",
                        "agent_registers.contact_no",
                        "agent_registers.fullname",
                        "agent_registers.email",
                        "agent_registers.designation",
                        "agent_registers.address",
                        "agent_registers.DOB",
                      )
                      ->where('referral', 'like', '%' . $user->referral_code . '%')
                      ->get();
        if($level1Agents->isEmpty())
        {
            return response()->json(['success' => 0,'error' => 'Data Not Found'],404);
        }
        
        return response()->json(['success'=>1,'downAgent'=> $level1Agents]);
    }

    public function AgentReward(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        $DG_Data = AgentDGSale::where('agent_id',$user->id)->first();
        
        if($DG_Data)
        {  
            $user_area = DB::table('agent_registers')
            ->leftJoin('plot_sales','agent_registers.id','=','plot_sales.agent_id')
            ->leftJoin('plots','plot_sales.plot_id','=','plots.id')
            ->select(
                DB::raw('SUM(plots.plot_area) as Total')
            )
            ->whereIn('plot_sales.plot_status', ['BOOKED', 'COMPLETED']) 
            ->where('agent_registers.id',$user->id)
            ->first();

            if($DG_Data->group>0)
            {
                $downAgentData = $this->showAgentDown($request);
                $ids=$downAgentData->original['downAgent']->pluck('id');
                // return response()->json($ids);
                if($ids)
                {
                    $underlink_areas = [];
    
                    foreach($ids as $id)
                    {
                        $DG_UnderLinkData = AgentDGSale::where('agent_id',$id)->first();
                        if($DG_UnderLinkData)
                        {
                            $user_UnderLinkarea = DB::table('agent_registers')
                            ->leftJoin('plot_sales','agent_registers.id','=','plot_sales.agent_id')
                            ->leftJoin('plots','plot_sales.plot_id','=','plots.id')
                            ->select(
                                DB::raw('SUM(plots.plot_area) as Total')
                            )
                            ->whereIn('plot_sales.plot_status', ['BOOKED', 'COMPLETED']) 
                            ->where('agent_registers.id',$id)
                            ->first();
    
                            if ($user_UnderLinkarea && isset($user_UnderLinkarea->Total)) {
                                array_push($underlink_areas, $user_UnderLinkarea->Total); // Use array_push
                                // or $group_area[] = $user_area->Total; // You can also do this
                            }
                        }   
                    }
                }    
                $group_area=array_sum($underlink_areas);
            }

                 
            if($DG_Data->direct >= 2 && $user_area->Total > 100)
            {
                $Reward_Achieved = 'MOBILE';
                $Reward_Received = 0;
                $Next_Reward = 'HPCHROME';
                $Area_Sold = (int) $user_area->Total;
                // return response()->json($Area_Sold);
            }
            else if($DG_Data->direct >= 6 && $user_area->Total >= 300)
            {
                $Reward_Achieved = 'HPCHROME';
                $Reward_Received = 0;
                $Next_Reward = 'SONYLED';
                $Area_Sold=$user_area->Total;
            }
            else if($DG_Data->direct >= 8 && $user_area->Total > 400)
            {
                $Reward_Achieved = 'SONYLED';
                $Reward_Received = 0;
                $Next_Reward = 'SCOOTY';
                $Area_Sold=$user_area->Total;
            }
            else if($DG_Data->direct >= 2 &&  $DG_Data->group >= 25 && $group_area >= 1350)
            {
                $Reward_Achieved = 'SCOOTY';
                $Reward_Received = 0;
                $Next_Reward = 'PULSAR';
                $Area_Sold=$group_area;
            }
            else if($DG_Data->direct >= 2 &&  $DG_Data->group >= 50 && $group_area >= 2600)
            {
                $Reward_Achieved = 'SCOOTY';
                $Reward_Received = 0;
                $Next_Reward = 'HARLEY';
                $Area_Sold=$group_area;
            }
            else if($DG_Data->direct >= 2 &&  $DG_Data->group >= 200 && $group_area >= 10100)
            {
                $Reward_Achieved = 'HARLEY';
                $Reward_Received = 0;
                $Next_Reward = 'WAGONR';
                $Area_Sold=$group_area;
            }
            else if($DG_Data->direct >= 2 &&  $DG_Data->group >= 400 && $group_area >= 20100)
            {
                $Reward_Achieved = 'WAGONR';
                $Reward_Received = 0;
                $Next_Reward = 'ERTIGA';
                $Area_Sold=$group_area;
            }
            else if($DG_Data->direct >= 2 &&  $DG_Data->group >= 800 && $group_area >= 40100)
            {
                $Reward_Achieved = 'ERTIGA';
                $Reward_Received = 0;
                $Next_Reward = 'SCORPIO';
                $Area_Sold=$group_area;
            }
            else if($DG_Data->direct >= 2 &&  $DG_Data->group >= 1600 && $group_area >= 80100)
            {
                $Reward_Achieved = 'SCORPIO';
                $Reward_Received = 0;
                $Next_Reward = 'FORTUNER';
                $Area_Sold=$group_area;
            }
            else if($DG_Data->direct >= 4 &&  $DG_Data->group >= 3200 && $group_area >= 160200)
            {
                $Reward_Achieved = 'FORTUNER';
                $Reward_Received = 0;
                $Next_Reward = 'FARMHOUSEAUDI';
                $Area_Sold=$group_area;
            }
            else if($DG_Data->direct >= 4 &&  $DG_Data->group >= 6400 && $group_area >= 320200)
            {
                $Reward_Achieved = 'FARMHOUSEAUDI';
                $Reward_Received = 0;
                $Next_Reward = 'NIL';
                $Area_Sold=$group_area;
            }
            else if($DG_Data->direct <= 2 || $user_area->Total <= 100 )
            {
                return response()->json(['success' => 0,'message' => "AGENT $user->fullname Reward Plan in Not Active Please Achieve the required quota first"],200); 
                // $Reward_Achieved = 'NIL';
                // $Reward_Received = 0;
                // $Next_Reward = '';
                // $Area_Sold=;  
            }
            
            $reward=AgentReward::where('agent_id',$user->id)->first();
            if($reward)
            {
                $reward->update([
                    'Agent_id' => $user->id,
                    'Direct' => $DG_Data->direct,
                    'Group' => $DG_Data->group,
                    'Reward_Achieved' => $Reward_Achieved,
                    'Reward_Received' => $Reward_Received,
                    'Next_Reward'=> $Next_Reward,
                    'Area_Sold' => $Area_Sold
                ]);
                return response()->json(['success' => 1,'message' => "AGENT $user->fullname Reward Updated",'data'=>$reward],200);
            }
            $Reward=AgentReward::create([
                'Agent_id' => $user->id,
                'Direct' => $DG_Data->direct,
                'Group' => $DG_Data->group,
                'Reward_Achieved' => $Reward_Achieved,
                'Reward_Received' => $Reward_Received,
                'Next_Reward'=> $Next_Reward,
                'Area_Sold' => $Area_Sold
            ]);
            return response()->json(['success' => 1,'message' => "AGENT $user->fullname Reward Created",'data'=>$Reward],200);
        }
        return response()->json(['success' => 0,'error' => "Agent don't have Direct Sale"],404);
    }

    public function GetAgentReward(Request $request)
    {
        $user = Auth::guard('sanctum')->user(); 
        $AgentReward = AgentReward::where('agent_id',$user->id)->first();
        if($AgentReward)
        {
            return response()->json(['success' => 1,'message' => "AGENT $user->fullname Reward ",'data'=>$AgentReward],200);   
        }
        return response()->json(['success' => 0,'message' => "AGENT {$user->fullname} has no Agent Rewards"],400);   
    }
}
