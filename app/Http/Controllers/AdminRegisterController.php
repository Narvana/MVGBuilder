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
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/'
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

        $admin = AdminRegister::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if(!$admin)
        {
            return response()->json([
                'success' => 0,
                'error' => 'Admin Not Registered. Call to Support System'
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
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/'
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

        $admin = AdminRegister::where('email', $request->email)->first();
        if(!$admin)
        {
            return response()->json([
                'success' => 0,
                'error' => 'Email don\'t exist'
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
                'token' => $token,
                'expire' => 1440,
            ], 200);
        }
        return response()->json([
            'success' => 0,
            'error' => 'Invalid credentials or Wrong Password'
        ], 401);

    }

    public function profileAdmin(Request $request){
        $admin = Auth::guard('sanctum')->user();

        if(!$admin){
            return response()->json([
                'success' => 0,
                'error'=>'Unauthorized, Admin not found',
            ],404);
        }
        return response()->json([
            'success' => 1,
            'admin' => $admin,
        ], 200);
    }   

    public function changePasswordAdmin(Request $request)
    {
        try {
            //code...
            $admin=Auth::guard('sanctum')->user(); 

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

            if(!$admin){
                return response()->json([
                    'success' => 0,
                    'error' => 'Admin Not Found'
                ], 404);
            }
            else{
                if($admin && Hash::check($request->oldPassword, $admin->password))
                {
                    if($request->newPassword === $request->verifyPassword)
                    {
                        $admin->password=Hash::make($request->newPassword);
                        $admin->save();
                        return response()->json(['success'=>1, 'message' => 'Password Updated'], 201);
                    }
                    else{
                        return response()->json(['success'=>0, 'error' => 'New Password and Verify Password should match each other'], 400);                        
                    }
                }
                return response()->json(['success'=>0, 'error' => "Current Password Don't Matchs"], 400);
            }

        } catch (\Throwable $th) {
            return response()->json(['success'=>0, 'error' => $th->getMessage()], 500);
        }
    }

}
