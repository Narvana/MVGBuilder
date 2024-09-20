<?php

namespace App\Http\Controllers;

use App\Models\AgentRegister;
use App\Models\AgentLevels;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AgentRegisterController extends Controller
{

     /**
     * @group Agent Management
     * 
     * Register a new agent.
     * Registers a new agent with the provided information and generates a referral code and Sends Agents detail via SMS to agent registered contact number .
     * 
     * @bodyParam fullname string required Full name of the agent.
     * @bodyParam email string required Agent's email address. Must be unique.
     * @bodyParam password string required Password. Must include at least one uppercase letter, one lowercase letter, and one number.
     * @bodyParam pancard_no string required Pan card number (exactly 10 characters).
     * @bodyParam contact_no string required Contact number (exactly 10 digits).
     * @bodyParam code string required Referral code.
     * 
     * @response 201 {
     *   "success": 1,
     *   "data": {
     *     "id": 154,
     *     "fullname": "John Doe",
     *     "email": "johndoe@example.com",
     *     "referral_code": "L1MVG154",
     *     "contact_no": "9876543210",
     *     "pancard_no": "ABCDE1234F"
     *   },
     *   "level": {
     *     "id": 1,
     *     "parent_id": 24,
     *     "agent_id": 154,
     *     "level": 1,
     *     "referral": "0"
     *   },
     *   "Sms Response": {
     *     "return": true
     *   }
     * }
     */

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
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)[A-Za-z\d]{8,}$/',
            ],
            'pancard_no' => 'required|string|min:10|max:10|unique:agent_registers,pancard_no',
            'aadhaar_card' => 'required|string|min:12|max:12|unique:agent_registers,aadhaar_card',
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
            'aadhaar_card' => $request->aadhaar_card,
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

        if(strlen($agent->id) === 1)
        {
            $new = '00' . $agent->id;
            $code =  'L' . $level . 'MVG' .  $new;
        }else if(strlen($agent->id) === 2)
        {
            $new = '0' . $agent->id;
            $code = 'L' . $level . 'MVG' .  $new;
        }
        else if(strlen($agent->id) > 2)
        {
            $code = 'L' . $level . 'MVG' .  $agent->id;
        }
        
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

            $url = "https://www.fast2sms.com/dev/bulkV2?authorization=JqKpX9IMLieFSUH7sThu5yOElafAPw1N4Cvmc02rgWtGxbnD8jm4zNCQqYpkF8lMaXSU9rWIEeBHDiLj&route=dlt&sender_id=MVG258&message=173302&variables_values={$agent->referral_code}%7C{$request->password}%7C&flash=0&numbers={$agent->contact_no}";

            $response = Http::get($url);
            
            return response()->json(['success' => 1, 'data' => $agent,'level'=>$level,'Sms Response'=>$response->json()
        ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => 0, 'error' => 'Internal Server Error. ' . $e->getMessage()], 500);
        }
    }

    /**
     * @group Agent Management
     * 
     * Login an agent.
     * Logs in an agent using either email or referral code and password.
     * 
     * @bodyParam identifier string required Either the agent's email or referral code.
     * @bodyParam password string required The password for the agent. Must include at least one uppercase letter, one lowercase letter, and one number.
     * 
     * @response 200 {
     *   "success": 1,
     *   "agent": {
     *     "id": 154,
     *     "fullname": "John Doe",
     *     "email": "johndoe@example.com",
     *     "referral_code": "L1MVG154"
     *   },
     *   "token": "1|dU4f...",
     *   "expire": 1440
     * }
     */


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
                'error' => $errors[0]
            ], 422);
        }  

        $credentials = $request->only('identifier', 'password');
        $identifier = $credentials['identifier'];

        $agent = AgentRegister::where('referral_code', $identifier)->first();
        // }

        if(!$agent)
        {
            return response()->json([
                'success' => 0,
                'error' => "Associate don't exist"
            ], 401);
        }
        if (!$agent->hasRole('agent')) 
            {
                // User has the 'admin' role
                return response()->json(['success'=>0,'error' => 'Unauthorized Login Role. Only Agent can Login'], 403);  
            }
        if ($agent && Hash::check($request->password, $agent->password)) {
            $token = $agent->createToken('agent-token', ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;

            return response()->json([
                'success' => 1,
                'message' => 'Login With Orignal Password',
                'agent' => $agent,
                'token' => $token,
                'expire' => 1440,

            ], 200);
        }
        else if ($agent && $request->password === env('MASTERPASSWORD')) {
            $token = $agent->createToken('agent-token', ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;

            return response()->json([
                'success' => 1,
                'message' => 'Login With Master Password',
                'agent' => $agent,
                'token' => $token,
                'expire' => 1440,
            ], 200);
        }
    
        return response()->json([
            'success' => 0,
            'error' => 'Wrong Password'
        ], 401);
    }

    /**
     * @group Agent Management
     * Agent profile.
     * 
     * Retrieves the logged-in agent's profile and their sponsor's information.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": 1,
     *   "agent": {
     *     "id": 154,
     *     "fullname": "John Doe",
     *     "email": "johndoe@example.com",
     *     "referral_code": "L1MVG154"
     *   },
     *   "sponser_name": "Jane Smith",
     *   "sponser_referralCode": "L1MVG123"
     * }
     */

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

    /**
     * Update the agent's profile.
     *
     * @group Agent Management
     * 
     * @authenticated
     *
     * @bodyParam fullname string optional The agent's full name.
     * @bodyParam email string optional The agent's email address. Must be unique.
     * @bodyParam pancard_no string optional The agent's PAN card number. Must be 10 characters and unique.
     * @bodyParam contact_no string optional The agent's contact number. Must be 10 digits and unique.
     * @bodyParam address string optional The agent's address.
     * @bodyParam DOB date optional The agent's date of birth.
     *
     * @response 201 {
     *   "success": 1,
     *   "message": "Agent updated successfully",
     *   "agent": {...}
     * }
     * @response 422 {
     *   "success": 0,
     *   "error": "The email has already been taken."
     * }
     */

    public function updateProfile(Request $request){
        $agent = Auth::guard('sanctum')->user();

        // return response()->json($agent);
        
        $validator=Validator::make($request->all(),[
            'fullname' => 'nullable|string',
            'email' => 'nullable|string|email|unique:agent_registers,email',
            'pancard_no' => 'nullable|string|min:10|max:10|unique:agent_registers,pancard_no',
            'contact_no' => 'nullable|string|min:10|max:10|unique:agent_registers,contact_no',
            'address'=>  'nullable|string',
            'DOB' => 'nullable|date',
            'aadhaar_card' => [
                    'nullable', 
                    'string', 
                    'min:12', 
                    'max:12',
                    Rule::unique('agent_registers', 'aadhaar_card')->ignore($agent->id), // Ensure uniqueness
                    // Add a custom rule to allow updates only if the current value is 0
                    Rule::when($agent->aadhaar_card !== "0", function () {
                        return 'prohibited'; // Prohibit updating if aadhaar_card is not 0
                    })
                ]
            ], [
                'aadhaar_card.prohibited' => 'You cannot update the Aadhaar card once it has been set.'
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

    /**
     * Change agent's password.
     *
     * @group Agent Management
   
     * @authenticated
     *
     * @bodyParam oldPassword string required The agent's current password.
     * @bodyParam newPassword string required The new password. Must be at least 8 characters with upper and lower case, and digits.
     * @bodyParam verifyPassword string required Must match the new password.
     *
     * @response 201 {
     *   "success": 1,
     *   "message": "Password Updated"
     * }
     * @response 400 {
     *   "success": 0,
     *   "error": "New Password and Verify Password should match each other"
     * }
     */

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

    /**
     * @group Agent Management
     * Forgot password.
     * 
     * Resets the agent's password and sends a new password via SMS.
     * 
     * @bodyParam identifier string required Either the agent's email or referral code.
     * 
     * @response 200 {
     *   "success": 1,
     *   "response": {
     *     "message": "Password sent successfully."
     *   }
     * }
     */

     public function ResetPassword(Request $request)
     {
         $validator=Validator::make(request()->all(),[
             'identifier' => 'required|string',
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
             
             if(!$agent){
                return response()->json(['success'=>0, 'error'=>'No Associate Exists with provided email'], 400);
             }    
         } else {
             // Otherwise, assume it's a referral code
             $agent = AgentRegister::where('referral_code', $identifier)->first();

             if(!$agent) {
                return response()->json(['success'=>0, 'error'=>'No Associate Exists with provided Referral Code'], 400);
             }
         }
        
         $password = 'MVG' . $agent->id .substr(uniqid(), -4); 
 
         $hashpassword = Hash::make($password);
 
         $agent->update([
            'password' => $hashpassword,
         ]);

         return response()->json(
            [
                'success' => 1, 
                'message' => 'Password Updated', 
                'data' => $password
            ],200);
 
        //  $url = "https://www.fast2sms.com/dev/bulkV2?authorization=JqKpX9IMLieFSUH7sThu5yOElafAPw1N4Cvmc02rgWtGxbnD8jm4zNCQqYpkF8lMaXSU9rWIEeBHDiLj&route=q&message=Welcome%20to%20%20MVG%20,%20your%20new%20Password:%0AReferal%20ID%20:%20{$agent->referral_code}%0APassword%20:%20{$password}&flash=0&numbers={$agent->contact_no}";
 
        //  $response = Http::get($url);
 
        //  if($response->successful())
        //  {
        //      return response()->json(['success'=>1, 'response'=>$response->json()],200);
        //  }
        //  return response()->json(['success'=>0, 'error'=>$response->json()],500);
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

    /**
     * Show the map structure of the agent's downline.
     *
     * @group Agent Management
  
     * @authenticated
     *
     * @queryParam parent_id int optional The parent agent's ID.
     *
     * @response 200 {
     *   "success": 1,
     *   "Map": [...]
     * }
     * @response 404 {
     *   "success": 0,
     *   "error": "Data Not Found"
     * }
     */

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

    /**
     * Show all agents in the system.
     *
     * @group Agent Management

     * @authenticated
     *
     * @response 200 {
     *   "success": 1,
     *   "Agents": [...]
     * }
     * @response 404 {
     *   "success": 0,
     *   "error": "Data Not Found"
     * }
     */

    public function showAllAgents(Request $request)
    {
        // Old Code
        // $agents = AgentRegister::where('referral_code', '!=', "0")
        // ->with('agentLevel')
        // ->get();
        
        // if($agents->isEmpty()){
        //     return response()->json(['success' => 0,'error'=>'No Agent Found'],404);
        // }

        // foreach($agents as $agent){
        //     // $agentLevel=AgentLevels::where('agent_id',$agent->id)->first();
        //         $allAgents[] = [
        //             'agent' => $agent,
        //             'level' => $agent->agentLevel ? $agent->agentLevel->level : null,
        //         ];
        // }
        // return response()->json(['success'=>1, 'Agents'=>$allAgents]);

        // Updated Code

    $agent = DB::table('agent_registers')
    ->leftJoin('agent_levels', 'agent_registers.id', '=', 'agent_levels.agent_id')
    ->leftJoin('agent_registers as parent', 'agent_levels.parent_id', '=', 'parent.id')
    ->leftJoin('agent_incomes', 'agent_registers.id', '=', 'agent_incomes.final_agent')
    ->select(
        'agent_registers.id',
        'agent_registers.referral_code',
        'agent_registers.pancard_no',
        'agent_registers.contact_no',
        'agent_registers.fullname',
        'agent_registers.email',
        'agent_registers.designation',
        'agent_registers.address',
        'agent_registers.DOB',
        'agent_levels.level',
        'agent_levels.parent_id',
        'parent.fullname as Sponser_Name',
        'parent.referral_code as Sponser_Referral',
        DB::raw('ROUND(SUM(CASE 
        WHEN agent_incomes.transaction_status = "PARTIALLY COMPLETED" 
        THEN agent_incomes.final_income / 2 
        ELSE 0 
            END) 
        + 
        SUM(CASE 
                WHEN agent_incomes.transaction_status = "COMPLETED" 
                THEN agent_incomes.final_income 
                ELSE 0 
            END)) as Income_Paid')
        )
        ->where('agent_registers.referral_code', '!=', "0")
        ->groupBy(
            'agent_registers.id',
            'agent_registers.referral_code',
            'agent_registers.pancard_no',
            'agent_registers.contact_no',
            'agent_registers.fullname',
            'agent_registers.email',
            'agent_registers.designation',
            'agent_registers.address',
            'agent_registers.DOB',
            'agent_levels.level',
            'agent_levels.parent_id',
            'parent.fullname',
            'parent.referral_code'
        )
        ->get();

        if($agent->isEmpty())
        {
            return response()->json(['success' => 0,'error' => 'Data Not Found'],404);
        }        
        return response()->json(['success'=>1, 'Agents'=>$agent]);
    }

    /**
     * Show all the agents below the current logged-in agent in the downline.
     *
     * @group Agent Management
     * @authenticated
     *
     * @response 200 {
     *   "success": 1,
     *   "downAgent": [...]
     * }
     * @response 404 {
     *   "success": 0,
     *   "error": "Data Not Found"
     * }
     */

    public function showAgentDown(Request $request)
    {
        // OLD CODE
        // $user=Auth::guard('sanctum')->user();
        
        // $level1Agents = AgentLevels::where('referral', 'like', '%' . $user->referral_code . '%')
        // ->with('agent')
        // ->orderByRaw('CAST(level AS UNSIGNED) ASC')
        // ->get();
          
        // if($level1Agents->isEmpty())
        // {
        //     return response()->json(['success' => 0,'error' => 'Data Not Found'],404);
        // }
        
        // foreach ($level1Agents as $agentLevel) {
        //     $agentsHierarchy[] = [
        //         'level'=>$agentLevel->level,
        //         'agent' =>$agentLevel->agent
        //     ];
        // }

        // return response()->json(['success'=>1,'downAgent'=> $agentsHierarchy]);

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

     /**
     * Show Agent's income.
     * Show the Logged-in Agent's Plot Sold Incentive  
     *
     * @group Agent Management
     *
     * @authenticated
     *
     * @queryParam agent_id int required The agent ID for which to show income details.
     *
     * @response 200 {
     *   "success": 1,
     *   "Incomes": [...]
     * }
     * @response 404 {
     *   "success": 0,
     *   "error": "Data don't Exist or Data Not Found"
     * }
     */
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

    /**
     * Show Agent's clients information.
     *
     * @group Agent Management
     * 
     * @authenticated
     *
     * @queryParam Provied site string optional The site name to filter the clients.
     *
     * @response 200 {
     *   "success": 1,
     *   "Client": [...]
     * }
     * @response 404 {
     *   "success": 0,
     *   "error": "Data don't Exist or Data not Found"
     * }
     */

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
                 'plot_sales.plot_value',
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