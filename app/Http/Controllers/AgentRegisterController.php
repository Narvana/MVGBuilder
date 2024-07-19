<?php

namespace App\Http\Controllers;

use App\Models\AgentRegister;
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

        if(!$agent)
        {
            return response()->json(['success'=>0,'message'=>'Agent Not Registered. Call to Support System'],400);
        }
        return response()->json(['success'=>1,'data'=>$agent],201);
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
            'message' => 'Invalid credentials'
        ], 401);

    }

    public function profile(Request $request){
        $agent = Auth::user();

        return response()->json([
            'success' => 1,
            'agent' => $agent,
        ], 200);
    }
}
