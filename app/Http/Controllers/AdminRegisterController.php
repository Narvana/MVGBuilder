<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminRegister;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminRegisterController extends Controller
{
    //
    public function registerAdmin(Request $request){
        $validator=Validator::make($request->all(),[
            'name' => 'required|string',
            'email' => 'required|string|email|unique:admin_registers',
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

        $admin = AdminRegister::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if(!$admin)
        {
            return response()->json([
                'success' => 0,
                'message' => 'Admin Not Registered. Call to Support System'
            ],400);
        }
        $admin->assignRole('admin');
        return response()->json(['success'=>1,'data'=>$admin],201);
    }

    public function loginAdmin(Request $request)
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

        $admin = AdminRegister::where('email', $request->email)->first();
        if(!$admin)
        {
            return response()->json([
                'success' => 0,
                'message' => 'Email don\'t exist'
            ], 401);
        }
        if (!$admin->hasRole('admin')) 
        {
            // User has the 'admin' role
            return response()->json(['success'=>0,'error' => 'Unauthorized Login Role. Only Admin can Login'], 401);  
        }
        if ($admin && Hash::check($request->password, $admin->password)) {
            // Create a token for the user

            $token = $admin->createToken('admin-token', ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;

            return response()->json([
                'success' => 1,
                'admin' => $admin,
                'token' => $token
            ], 200);
        }
        return response()->json([
            'success' => 0,
            'message' => 'Invalid credentials or Wrong Password'
        ], 401);

    }

    public function profileAdmin(Request $request){
        $admin = Auth::user();

        if(!$admin){
            return response()->json([
                'success' => 0,
                'message'=>'Unauthorized, Admin Value not found',
            ]);
        }
        return response()->json([
            'success' => 1,
            'admin' => $admin,
        ], 200);
    }   
}
