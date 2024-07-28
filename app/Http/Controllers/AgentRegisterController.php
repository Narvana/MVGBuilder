<?php

namespace App\Http\Controllers;

use App\Models\AgentRegister;
use App\Models\AgentProfile;
use App\Models\AgentLevels;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
            'pancard_no' => 'required|string|unique:agent_registers,pancard_no',
            'contact_no' => 'required|string|unique:agent_registers,contact_no',
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
                'errors' => $formattedErrors
            ], 422);
        }


        $agent_id=AgentRegister::where('referral_code',$request->code)->first();
        $agent_level=AgentLevels::where('agent_id',$agent_id->id)->first();
        if(!$agent_id)
        {
            return response()->json(['success'=>0,'message'=>'Enter a correct referral code']);
        }
        if($agent_level?->level==="10")
        {
            return response()->json(['success'=>0,'message'=>"You don't have the access to register New Agent"]);
        }

        $code= substr($request->fullname, 0, 3) . Str::random(10);

        $agent = AgentRegister::create(
            [
            'fullname' => $request->fullname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'referral_code' => $code,
            'contact_no' => $request->contact_no,
            'pancard_no' => $request->pancard_no,
            // ''
        ]);
        try {
            if(!$agent)
            {
                return response()->json([
                    'success' => 0,
                    'message' => 'Agent Not Registered. Call to Support System'
                ],400);
            }

            $agent->assignRole('agent');

            if($agent_id->referral_code === "0")
            {
                $level = 1 ;
            }
            else if($agent_level->level === "1" )
            {
                $level=2;
            }
            else if($agent_level->level=== "2")
            {
                $level= "3";
            }
            else if($agent_level->level=== "3")
            {
                $level= "4";
            }
            else if($agent_level->level==="4")
            {
                $level="5";
            }
            else if($agent_level->level==="5")
            {
                $level="6";
            }
            else if($agent_level->level==="6")
            {
                $level="7";
            }
            else if($agent_level->level==="7")
            {
                $level="8";
            }
            else if($agent_level->level==="8")
            {
                $level="9";
            }
            else if($agent_level->level==="9")
            {
                $level="10";
            }
            // else if{

            // }

            $level=AgentLevels::create([
                'parent_id'=>$agent_id->id,
                'agent_id'=>$agent->id,
                'level'=> $level,
            ]);
            return response()->json(['success' => 1, 'data' => $agent,'level'=>$level], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => 0, 'message' => 'Role assignment failed', 'error' => $e->getMessage()], 500);
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
                'errors' => $formattedErrors
            ], 422);
        }   

        $agent = AgentRegister::where('email', $request->email)->first();
        if(!$agent)
        {
            return response()->json([
                'success' => 0,
                'message' => 'Email don\'t exist'
            ], 401);
        }
        if (!$agent->hasRole('agent')) 
            {
                // User has the 'admin' role
                return response()->json(['success'=>0,'error' => 'Unauthorized Login Role. Only Agent can Login'], 401);  
            }
        if ($agent && Hash::check($request->password, $agent->password)) {
            // Create a token for the user
            $token = $agent->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => 1,
                'agent' => $agent,
                'token' => $token
            ], 200);
        }
    
        return response()->json([
            'success' => 0,
            'message' => 'Invalid credentials or Wrong Password'
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
                'profile'=>'Profile Value in Not Empty'
            ], 200);
        }
        return response()->json([
            'success' => 1,
            'agent' => $agent,
            'profile'=>$profile
        ], 200);
    }

    public function addProfile(Request $request){
        $agent = Auth::user();
        $agent_profile=AgentProfile::where('agent_id',$agent->id)->first();
        $validator=Validator::make($request->all(),[
            'agent_id' => $agent_profile ? 'nullable|integer|exists:agent_registers,id' : 'required|integer|exists:agent_registers,id',
            'designation' => $agent_profile ? 'nullable|string' : 'required|string',
            'description' =>  $agent_profile ? 'nullable|string' : 'required|string',
            'contact_no' =>  $agent_profile ? 'nullable|string|min:10|max:10' : 'required|string|min:10|max:10',
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
                'errors' => $formattedErrors
            ], 422);
        }   

        $profile=$request->only(['designation','description','contact_no','address']);
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
         'contact_no'=>$profile['contact_no'],
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
                    'errors' => $formattedErrors
                ], 422);
            }   

            if(!$agent){
                return response()->json([
                    'success' => 0,
                    'message' => 'Agent Not Found'
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
                        return response()->json(['success'=>0, 'message' => 'New Password and Verify Password should match each other'], 400);                        
                    }
                }
                return response()->json(['success'=>0, 'message' => 'Old Password Don\'t Matchs'], 400);
            }

        } catch (\Throwable $th) {
            return response()->json(['success'=>0,'message' => 'Something went wrong', 'details' => $th->getMessage()], 500);
        }
    }

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

    public function showSingleLevel(Request $request)
    {

        $user=Auth::guard('sanctum')->user();


        $params=$request->query('parent_id');

        $level1Agents = AgentLevels::where('parent_id', $params ?? $user->id)->get();

        if($level1Agents->isEmpty())
        {
            return response()->json(['success'=>0,'message'=>'Data Not Found'],404);
        }

        foreach ($level1Agents as $agent) {
            $agentsHierarchy[] = [
                'level'=>$agent->level,
                'agent' => AgentRegister::where('id', $agent->agent_id)->first(),
            ];
        }
        return response()->json($agentsHierarchy);   
    }

    public function showAllAgents(Request $request)
    {
        $agents = AgentRegister::where('referral_code','!=', "0")->get();
        if($agents->isEmpty()){
            return response()->json(['success'=>0,'message'=>'No Agent Found'],404);
        }
        foreach($agents as $agent){
            $agentLevel=AgentLevels::where('agent_id',$agent->id)->first();
            $allAgents[] = [
                'agent' => $agent,
                'level' => $agentLevel->level,
             ];
        }
        return response()->json(['success'=>1,'Agents'=>$allAgents]);
    }
}
