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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\select;

class AgentRegisterController extends Controller
{
    //
    public function registerAgent(Request $request){
    try {

        DB::beginTransaction();
        
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string',
            'email' => 'required|string|email|unique:agent_registers,email',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/',
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

        $agent_level=AgentLevels::where('agent_id',$agent_id->id)->first();

        $agent = AgentRegister::create([
            'fullname' => $request->fullname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'referral_code' => 1,
            'contact_no' => $request->contact_no,
            'pancard_no' => $request->pancard_no,
        ]);

        if(!$agent)
        {
            return response()->json([
                'success' => 0,
                'error' => 'Agent Not Registered. Call to Support System'
            ],400);
        }

         $level = $agent_level ? intval($agent_level->level) + 1 : 1;


        if(strlen($level) === 1)
        {

            $new = '0' . $level;
            $code = 'MVG' . $agent->id . 'L' . $level . $new;

        }else if(strlen($level)>1)
        {
            $code = 'MVG' . $agent->id . 'L' . $level . $level;
        }

            // return response()->json($code);

        $agent->update([
            'referral_code' => $code,
        ]);   

        $agent->assignRole('agent');

        if($agent_id->referral_code === "0")
        {
            $level = 1;
            $referral="0";
            // return response()->json([$level , $referral])
        }
        else{
            $level= $level;
            $referral= $agent_level->referral  . $request->code;  
            // return response()->json([$level , $referral]);
        }

        $level=AgentLevels::create([
            'parent_id'=>$agent_id->id,
            'agent_id'=>$agent->id,
            'level'=> $level,
            'referral'=>$referral
        ]);

        DB::commit();

            // $url = "https://www.fast2sms.com/dev/bulkV2?authorization=JqKpX9IMLieFSUH7sThu5yOElafAPw1N4Cvmc02rgWtGxbnD8jm4zNCQqYpkF8lMaXSU9rWIEeBHDiLj&route=q&message=Welcome%20to%20%20MVG%20Builders,%20your%20details%20are:%0AReferal%20ID%20:%20{$agent->referral_code}%0APassword%20:%20{$request->password}&flash=0&numbers={$agent->contact_no}";

            // $response = Http::get($url);
            // 'Sms Response'=>$response->json()
            return response()->json(['success' => 1, 'data' => $agent,'level'=>$level,
        ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => 0, 'error' => 'Internal Server Error. ' . $e->getMessage()], 500);
        }
    }

    public function loginAgent(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'identifier' => 'required|string',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/'
            ],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return response()->json([
                'success' => 0,
                'error' => $errors[0] // Return the first error message
            ], 422);
        }  

        $credentials = $request->only('identifier', 'password');
        $identifier = $credentials['identifier'];

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $agent = AgentRegister::where('email', $identifier)->first();
        } else {
            if($identifier === "0")
            {
                return response()->json([
                    'success' => 0,
                    'error' => "Super Agent should login with email"
                ], 401);
            }
            // Otherwise, assume it's a referral code
            $agent = AgentRegister::where('referral_code', $identifier)->first();
        }

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
                return response()->json(['success'=>0,'error' => 'Unauthorized Login Role. Only Agent can Login'], 403);  
            }
        if ($agent && Hash::check($request->password, $agent->password)) {
            // Create a token for the user
            $token = $agent->createToken('agent-token', ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;
            // createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => 1,
                'agent' => $agent,
                'token' => $token,
                'expire' => 1440,
            ], 200);
        }
    
        return response()->json([
            'success' => 0,
            'error' => 'Invalid credentials or Wrong Password'
        ], 401);

    }

    public function forgotPassword(Request $request)
    {
        $validator=Validator::make(request()->all(),[
            'identifier' => 'required|String',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return response()->json([
                'success' => 0,
                'error' => $errors[0] // Return the first error message
            ], 422);
        }  

        $credentials = $request->only('identifier');
        $identifier = $credentials['identifier'];

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $agent = AgentRegister::where('email', $identifier)->first();
        } else {
            // Otherwise, assume it's a referral code
            $agent = AgentRegister::where('referral_code', $identifier)->first();
        }

        $password = 'MVG' . $agent->id .substr(uniqid(), -4); 

        $hashpassword = Hash::make($password);

        $agent->update([
            'password' => $hashpassword,
        ]);

        $url = "https://www.fast2sms.com/dev/bulkV2?authorization=JqKpX9IMLieFSUH7sThu5yOElafAPw1N4Cvmc02rgWtGxbnD8jm4zNCQqYpkF8lMaXSU9rWIEeBHDiLj&route=q&message=Welcome%20to%20%20MVG%20,%20your%20new%20Password:%0AReferal%20ID%20:%20{$agent->referral_code}%0APassword%20:%20{$password}&flash=0&numbers={$agent->contact_no}";

        $response = Http::get($url);

        if($response->successful())
        {
            return response()->json(['success'=>1, 'response'=>$response->json()],200);
        }
        return response()->json(['success'=>0, 'response'=>$response->json()],500);
    }

    public function profile(Request $request){
        $agent = Auth::guard('sanctum')->user();

        $agentLevel = AgentLevels::where('agent_id',$agent->id)->first();

        $agentParent = AgentRegister::where('id',$agentLevel->parent_id)->first(); 

        if(!$agent){
            return response()->json([
                'success' => 0,
                'error' => 'No Such Agent Exist or Some Token Issue may Occur'
            ], 400);
        }
        return response()->json([
            'success' => 1,
            'agent' => $agent,
            'sponser_name' => $agentParent->fullname,
            'sponser_referralCode' => $agentParent->referral_code
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

    public function updateProfile(Request $request){
        $agent = Auth::guard('sanctum')->user();

        $validator=Validator::make($request->all(),[
            'fullname' => 'nullable|string',
            'email' => 'nullable|string|email|unique:agent_registers,email',
            'pancard_no' => 'nullable|string|min:10|max:10|unique:agent_registers,pancard_no',
            'contact_no' => 'nullable|string|min:10|max:10|unique:agent_registers,contact_no',
            'address'=>  'nullable|string',
            'DOB' => 'nullable|date'
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
            $agent->update($data);        
            
            return response()->json([
                'success'=>1,
                'message' => 'Agent updated successfully',
                'agent' => $agent
            ], 201);
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
                    'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/'
                ],
                'newPassword'=>  [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/'
                ],
                'verifyPassword'=>[
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/'
                ],
            ]);

            
            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                return response()->json([
                    'success' => 0,
                    'error' => $errors[0] // Return the first error message
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
                    if($request->newPassword === $request->verifyPassword)
                    {
                        $agent->password=Hash::make($request->newPassword);
                        $agent->save();
                        return response()->json(['success'=>1, 'message' => 'Password Updated'], 201);
                    }
                    else{
                        return response()->json(['success'=>0, 'error' => 'New Password and Verify Password should match each other'], 400);                        
                    }
                }
                return response()->json(['success'=>0, 'error' => "Current Password Don't Matches"], 400);
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

        // $user=Auth::guard('sanctum')->user();

        // $params=$request->query('parent_id');

        // $level1Agents=DB::table('agent_levels')
        //   ->leftJoin('agent_registers','agent_levels.agent_id','=','agent_registers.id')
        //   ->select(
        //     "agent_levels.level",
        //     "agent_registers.id",
        //     "agent_registers.referral_code",
        //     "agent_registers.pancard_no",
        //     "agent_registers.contact_no",
        //     "agent_registers.fullname",
        //     "agent_registers.email",
        //     "agent_registers.designation",
        //     "agent_registers.address",
        //     "agent_registers.DOB",
        //   )->where('parent_id', $params ?? $user->id)->get();

        // if($level1Agents->isEmpty())
        // {
        //     return response()->json(['success'=>0,'error'=>'Data Not Found'],404);
        // }

        // return response()->json(['success'=>1, 'Map'=>$level1Agents],200);   
    }

    public function showAllAgents(Request $request)
    {
        // Old Code
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

        // Updated Code

        //  $agent = DB::table('agent_registers')
        //          ->leftJoin('agent_levels','agent_registers.id','=','agent_levels.agent_id')
        //          ->leftJoin('agent_registers as parent','agent_levels.parent_id', '=' , 'parent.id')
        //          ->select(
        //             'agent_registers.id',
        //             'agent_registers.referral_code',
        //             'agent_registers.pancard_no',
        //             'agent_registers.contact_no',
        //             'agent_registers.fullname',
        //             'agent_registers.email',
        //             'agent_registers.designation',
        //             'agent_registers.address',
        //             'agent_registers.DOB',
        //             'agent_levels.level',
        //             'agent_levels.parent_id',
        //             'parent.fullname as Sponser_Name',
        //             'parent.referral_code as Sponser_Referral'
        //             )
        //             ->where('agent_registers.referral_code','!=', "0")
        //             ->get();

        // if($agent->isEmpty())
        // {
        //     return response()->json(['success' => 0,'error' => 'Data Not Found'],404);
        // }        
        // return response()->json(['success'=>1, 'Agents'=>$agent]);
    }

    public function showAgentDown(Request $request)
    {
        // OLD CODE

        $user=Auth::guard('sanctum')->user();
        
        $level1Agents = AgentLevels::where('referral', 'like', '%' . $user->referral_code . '%')
        ->with('agent')
        ->orderByRaw('CAST(level AS UNSIGNED) ASC')
        ->get();
          
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

        return response()->json(['success'=>1,'downAgent'=> $agentsHierarchy]);

        //  UPDATED CODE
        // $user=Auth::guard('sanctum')->user();
        
        // $level1Agents=DB::table('agent_levels')
        //               ->leftJoin('agent_registers','agent_levels.agent_id','=','agent_registers.id')
        //               ->select(
        //                 "agent_levels.level",
        //                 "agent_registers.id",
        //                 "agent_registers.referral_code",
        //                 "agent_registers.pancard_no",
        //                 "agent_registers.contact_no",
        //                 "agent_registers.fullname",
        //                 "agent_registers.email",
        //                 "agent_registers.designation",
        //                 "agent_registers.address",
        //                 "agent_registers.DOB",
        //               )
        //               ->where('referral', 'like', '%' . $user->referral_code . '%')
        //               ->get();
          
        // if($level1Agents->isEmpty())
        // {
        //     return response()->json(['success' => 0,'error' => 'Data Not Found'],404);
        // }
        
        // return response()->json(['success'=>1,'downAgent'=> $level1Agents]);
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
            return response()->json(['success' => 0,'error' =>"Data don't Exist or Data Not Found"],404);
        }

                return response()->json(['success'=>1,'Incomes'=>$income],200);
    }

    public function agentClientInfo(Request $request)
    {
        $user=Auth::guard('sanctum')->user();

        $params=$request->query('site');

        $plot_sales = DB::table('plot_sales')
        ->leftJoin('client_controllers', 'plot_sales.client_id', '=', 'client_controllers.id')
        ->leftJoin('plots', 'plot_sales.plot_id', '=', 'plots.id')
        ->leftJoin('sites','plots.site_id','=','sites.id')
        ->select(
                 'client_controllers.id as client_id',
                 'client_controllers.client_name',
                 'client_controllers.client_contact',
                 'client_controllers.client_address',
                 'plots.plot_No',
                 'plots.plot_type',
                 'sites.site_name',                
                 'plot_sales.totalAmount',
                 'plot_sales.plot_status'
                )->where('plot_sales.agent_id',$user->id);
        
        if ($params) 
        {
            $plot_sales->where('sites.site_name',$params);  
        }

        $plot_sales = $plot_sales->get();
        if($plot_sales->isEmpty())
        {
            return response()->json(['success' => 0,'error' => "Data don't Exist or Data not Found"],404);
        }
        return response()->json(['success'=>1,'Client'=>$plot_sales],200);
    }
}
