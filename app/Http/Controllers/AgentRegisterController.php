<?php

namespace App\Http\Controllers;

use App\Models\AgentRegister;
use App\Models\AgentProfile;
use App\Models\AgentLevels;
use App\Http\Controllers\Controller;
use App\Models\AgentIncome;
use App\Models\Plot_Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AgentRegisterController extends Controller
{
    //
    public function registerAgent(Request $request){
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string',
            'email' => 'required|string|email|unique:agent_registers,email',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/',
            ],
            'pancard_no' => 'required|string|min:10|max:10|unique:agent_registers,pancard_no',
            'contact_no' => 'required|string|min:10|max:10|unique:agent_registers,contact_no',
            'code' => 'required|string',
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


        $agent_id=AgentRegister::where('referral_code',$request->code)->first();
        // $agent_id=
        $agent_level=AgentLevels::where('agent_id',$agent_id->id)->first();

        if($agent_level?->level==="10")
        {
            return response()->json(['success'=>0,'error'=>"You don't have the access to register New Agent"]);
        }


        $agent = AgentRegister::create([
            'fullname' => $request->fullname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'referral_code' => 1,
            'contact_no' => $request->contact_no,
            'pancard_no' => $request->pancard_no,
        ]);
        
        try {
            if(!$agent)
            {
                return response()->json([
                    'success' => 0,
                    'error' => 'Agent Not Registered. Call to Support System'
                ],400);
            }

            $level = $agent_level ? intval($agent_level->level) + 1 : 1;
            $code = 'MVG' . 'L' . $level . $agent->id;

            $agent->update([
                'referral_code' => $code,
            ]);   

            $agent->assignRole('agent');

            if($agent_id->referral_code === "0")
            {
                $level = 1;
                $referral="0";
            }
            else if($agent_level->level === "1" )
            {
                $level=2;
                $referral= $agent_level->referral . $request->code ;   
            }
            else if($agent_level->level=== "2")
            {
                $level= "3";
                $referral= $agent_level->referral . $request->code ;  
            }
            else if($agent_level->level=== "3")
            {
                $level= "4";
                $referral= $agent_level->referral . $request->code ;  
            }
            else if($agent_level->level==="4")
            {
                $level="5";
                $referral= $agent_level->referral . $request->code ;  
            }
            else if($agent_level->level==="5")
            {
                $level="6";
                $referral= $agent_level->referral . $request->code ;  
            }
            else if($agent_level->level==="6")
            {
                $level="7";
                $referral= $agent_level->referral . $request->code ;  
            }
            else if($agent_level->level==="7")
            {
                $level="8";
                $referral= $agent_level->referral . $request->code ;  
            }
            else if($agent_level->level==="8")
            {
                $level="9";
                $referral= $agent_level->referral . $request->code ;  
            }
            else if($agent_level->level==="9")
            {
                $level="10";
                $referral= $agent_level->referral . $request->code ;  
            }
            // else if{

            // }

            $level=AgentLevels::create([
                'parent_id'=>$agent_id->id,
                'agent_id'=>$agent->id,
                'level'=> $level,
                'referral'=>$referral
            ]);
            return response()->json(['success' => 1, 'data' => $agent,'level'=>$level], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => 0, 'error' => 'Internal Server Error. ' . $e->getMessage()], 500);
        }
    }

    public function loginAgent(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'email' => 'required|string|email',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/'
            ],
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

        $agent = AgentRegister::where('email', $request->email)->first();
        if(!$agent)
        {
            return response()->json([
                'success' => 0,
                'error' => 'Email don\'t exist'
            ], 401);
        }
        if (!$agent->hasRole('agent')) 
            {
                // User has the 'admin' role
                return response()->json(['success'=>0,'error' => 'Unauthorized Login Role. Only Agent can Login'], 401);  
            }
        if ($agent && Hash::check($request->password, $agent->password)) {
            // Create a token for the user
            $token = $agent->createToken('agent-token', ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;
            // createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => 1,
                'agent' => $agent,
                'token' => $token
            ], 200);
        }
    
        return response()->json([
            'success' => 0,
            'error' => 'Invalid credentials or Wrong Password'
        ], 401);

    }

    public function profile(Request $request){
        $agent = Auth::user();
        $profile=AgentProfile::where('agent_id',$agent->id)->get();
        // $agent->id;

        if(!$profile){
            return response()->json([
                'success' => 1,
                'agent' => $agent,
                'profile'=>'Profile is Empty. Please Fill your profile data'
            ], 200);
        }
        return response()->json([
            'success' => 1,
            'agent' => $agent,
            'profile'=>$profile
        ], 200);
    }

    public function removeAgent(Request $request)
    {
        $params=$request->query('id');

        try {
            $agent = AgentRegister::find($params);
    
            if (!$agent) {
                return response()->json([
                    'success' => 0,
                    'error' => 'Agent Not Found'
                ], 404);
            }
    
            $agent->delete();
    
            return response()->json([
                'success' => 1,
                'message' => 'Agent Removed'
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => 0,
                'error' => 'An error occurred while trying to remove the agent. ' . $e->getMessage()
            ], 500);
        }
    }

    public function addProfile(Request $request){
        $agent = Auth::user();
        $agent_profile=AgentProfile::where('agent_id',$agent->id)->first();
        $validator=Validator::make($request->all(),[
            'agent_id' => $agent_profile ? 'nullable|integer|exists:agent_registers,id' : 'required|integer|exists:agent_registers,id',
            'designation' => $agent_profile ? 'nullable|string' : 'required|string',
            'description' =>  $agent_profile ? 'nullable|string' : 'required|string',
            'address'=>  $agent_profile ? 'nullable|string' : 'required|string' 
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

        $profile=$request->only(['designation','description','address']);
        if($agent_profile){
            if(isset( $request->fullname))
            {
                $agent->fullname = $request->fullname;
                $agent->save();
            }
            $agent_profile->update($profile);
            
            return response()->json([
                'success'=>1,
                'message' => 'Profile updated successfully',
                'profile' => $agent_profile,
                'agent' => $agent
            ], 201);
        }
        $agentprofile=AgentProfile::create([
         'agent_id'=>$agent->id,
         'designation'=>$profile['designation'],
         'description'=>$profile['description'],
         'address' => $profile['address'],
        ]);
        return response()->json(['success'=>1, 'message' => 'Profile created successfully', 'profile' => $agentprofile, 'agent'=>$agent], 201);
    }

    public function changePassword(Request $request)
    {
        try {
            //code...
            $agent=Auth::guard('sanctum')->user(); 

            $validator=Validator::make($request->all(),[
                'oldPassword'=>[
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/'
                ],
                'newPassword'=>  [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/'
                ],
                'verifyPassword'=>[
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/'
                ],
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

            if(!$agent){
                return response()->json([
                    'success' => 0,
                    'error' => 'Agent Not Found'
                ], 404);
            }
            else{
                if($agent && Hash::check($request->oldPassword, $agent->password))
                {
                    if($request->newPassword === $request->verifiyPassword)
                    {
                        $agent->password=Hash::make($request->newPassword);
                        $agent->save();
                        return response()->json(['success'=>1, 'message' => 'Password Updated'], 201);
                    }
                    else{
                        return response()->json(['success'=>0, 'error' => 'New Password and Verify Password should match each other'], 400);                        
                    }
                }
                return response()->json(['success'=>0, 'error' => 'Old Password Don\'t Matchs'], 400);
            }

        } catch (\Throwable $th) {
            return response()->json(['success'=>0, 'error' => $th->getMessage()], 500);
        }
    }

    // All Level
      public function showLevel(Request $request)
    {
        $user=Auth::guard('sanctum')->user();
        // if ($user->referral_code === "0") {
            // Fetch agents at level 1
        $level1Agents = AgentLevels::where('parent_id', $user->id)->get();
        
            foreach ($level1Agents as $agent) {
                $agentsHierarchy[] = [
                    'level' => $agent->level,
                    'agent' => AgentRegister::where('id', $agent->agent_id)->first(),
                    'down' => (AgentLevels::where('parent_id', $agent->agent_id )->get()) ? $this->fetchDownAgents($agent->agent_id, $agent->level+1) : []
                ];
            }
               return response()->json($agentsHierarchy);
    }

    private function fetchDownAgents($parentId, $level) {
        $agents = AgentLevels::where('parent_id', $parentId)->get();
        $result = [];

            foreach ($agents as $agent) {
                $result[] = [
                    'level' =>  $agent->level,
                    'agent' => AgentRegister::where('id', $agent->agent_id)->first(),
                    'down' =>(AgentLevels::where('parent_id', $agent->agent_id )->get()) ? $this->fetchDownAgents($agent->agent_id, $agent->level+1) : []
                ];
            }        
            return $result;
    }

    // Map
    public function showMap(Request $request)
    {

        $user=Auth::guard('sanctum')->user();

        $params=$request->query('parent_id');

        $level1Agents = AgentLevels::where('parent_id', $params ?? $user->id)->with('agent')->get();

        if($level1Agents->isEmpty())
        {
            return response()->json(['success'=>0,'error'=>'Data Not Found'],404);
        }

        foreach ($level1Agents as $agentLevel) {
            $agentsHierarchy[] = [
                'level'=>$agentLevel->level,
                'agent' =>$agentLevel->agent
            ];
        }
        return response()->json(['success'=>1, 'Map'=>$agentsHierarchy],200);   
    }

    public function showAllAgents(Request $request)
    {
        // $agents = AgentRegister::where('referral_code','!=', "0")->get();
        $agents = AgentRegister::where('referral_code', '!=', "0")
        ->with('agentLevel')
        ->get();
        
        if($agents->isEmpty()){
            return response()->json(['success' => 0,'error'=>'No Agent Found'],404);
        }

        foreach($agents as $agent){
            // $agentLevel=AgentLevels::where('agent_id',$agent->id)->first();
                $allAgents[] = [
                    'agent' => $agent,
                    'level' => $agent->agentLevel ? $agent->agentLevel->level : null,
                ];
        }
        return response()->json(['success'=>1, 'Agents'=>$allAgents]);
    }

    public function showAgentDown(Request $request)
    {
        $user=Auth::guard('sanctum')->user();
        
        $level1Agents = AgentLevels::where('referral', 'like', '%' . $user->referral_code . '%')->with('agent')->orderBy('level','asc')->get();
          
        if($level1Agents->isEmpty())
        {
            return response()->json(['success' => 0,'error' => 'Data Not Found'],404);
        }
        
        foreach ($level1Agents as $agentLevel) {
            $agentsHierarchy[] = [
                'level'=>$agentLevel->level,
                'agent' =>$agentLevel->agent
            ];
        }

        return response()->json(['success'=>1,'downAgent'=>$agentsHierarchy]);
    }

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

    public function agentClientInfo(Request $request)
    {
        $params=$request->query('agent_id');

        $plot_sales = DB::table('plot_sales')
        ->leftJoin('client_controllers', 'plot_sales.client_id', '=', 'client_controllers.id')
        ->leftJoin('plots', 'plot_sales.plot_id', '=', 'plots.id')
        ->select(
                 'client_controllers.client_name',
                 'client_controllers.client_contact',
                 'client_controllers.client_address',
                 'plots.plot_No',
                 'plots.plot_type',                'plot_sales.totalAmount',
                 'plot_sales.plot_status'
                )->where('plot_sales.agent_id',$params)->get();
        if($plot_sales->isEmpty())
        {
            return response()->json(['success' => 0,'error' => "Data don't Exist"],400);
        }

                return response()->json(['success'=>1,'Client'=>$plot_sales],200);
   
    }

}
