<?php

namespace App\Http\Controllers;

use App\Models\AgentRegister;
use App\Models\AgentProfile;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AgentRegisterController extends Controller
{
    //
    public function registerAgent(Request $request){
        $validator=Validator::make($request->all(),[
            'fullname' => 'required|string',
            'email' => 'required|string|email|unique:agent_registers',
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

        $agent = AgentRegister::create([
            'fullname' => $request->fullname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        try {
            $agent->assignRole('agent');
            return response()->json(['success' => 1, 'data' => $agent], 201);
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
        if (!$agent->hasRole('agent')) 
            {
                // User has the 'admin' role
                return response()->json(['error' => 'Unauthorized Login Role. Only Agent can Login'], 401);  
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
            $validator=Validator::make($request->all(),[
                'password'=>  [
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
            $agent=Auth::user();
            if(!$agent){
                return response()->json([
                    'success' => 0,
                    'message' => 'Agent Not Found'
                ], 404);
            }
            $agent->password=Hash::make($request->password);
            $agent->save();
            return response()->json(['success'=>1, 'message' => 'Password Updated'], 201);
        } catch (\Throwable $th) {
            return response()->json(['success'=>0,'message' => 'Something went wrong', 'details' => $th->getMessage()], 500);
        }

    }
}
