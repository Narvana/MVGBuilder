<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminRegister;
use App\Models\AgentRegister;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminRegisterController extends Controller
{
     /**
     * @group Admin Management
     * 
     * Register a new Admin 
     *
     * @bodyParam name string required The name of the admin.
     * @bodyParam email string required The email of the admin. Example: admin@example.com
     * @bodyParam password string required The password of the admin. Minimum 8 characters, at least one uppercase letter, one lowercase letter, and one number.
     * 
     * @response {
     *   "success": 1,
     *   "data": {
     *     "id": 1,
     *     "name": "Admin Name",
     *     "email": "admin@example.com"
     *   }
     * }
     */

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


    /**
     * @group Admin Management
     * 
     * Login as an Admin
     *
     * @bodyParam email string required The email of the admin. Example: admin@example.com
     * @bodyParam password string required The password of the admin. Minimum 8 characters, at least one uppercase letter, one lowercase letter, and one number.
     *
     * @response {
     *   "success": 1,
     *   "admin": {
     *     "id": 1,
     *     "name": "Admin Name",
     *     "email": "admin@example.com"
     *   },
     *   "token": "admin-token",
     *   "expire": 1440
     * }
     */
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
                'error' => "Email don't exist"
            ], 401);
        }
        if (!$admin->hasRole('admin')) 
        {
            // User has the 'admin' role
            return response()->json(['success'=>0,'error' => 'Unauthorized Login Role. Only Admin can Login'], 403);  
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

     /**
     * @group Admin Management
     * 
     * Get Admin Profile
     * 
     * @authenticated
     *
     * @response {
     *   "success": 1,
     *   "admin": {
     *     "id": 1,
     *     "name": "Admin Name",
     *     "email": "admin@example.com"
     *   }
     * }
     */
    public function profileAdmin(Request $request){
        $admin = Auth::guard('sanctum')->user();

        if(!$admin){
            return response()->json([
                'success' => 0,
                'error'=>'Admin details not found',
            ],404);
        }
        return response()->json([
            'success' => 1,
            'admin' => $admin,
        ], 200);
    }   

    /**
     * @group Admin Management
     * 
     * Change Admin Password
     *
     * @authenticated
     * @bodyParam oldPassword string required The current password of the admin.
     * @bodyParam newPassword string required The new password of the admin.
     * @bodyParam verifyPassword string required Must match the new password.
     *
     * @response {
     *   "success": 1,
     *   "message": "Password Updated"
     * }
     */
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

     /**
     * @group Admin Management
     * 
     * UPDATE an Agent By ADMIN 
     * If The Admin want to remove an existing agent and want to  
     * provide that particular id to new Agent or if Admin don't 
     * have The Agent It Will provide the MVG Information
     *
     * @authenticated
     * @queryParam id integer required The ID of the agent to remove.
     *
     * @response {
     *   "success": 1,
     *   "message": "Agent Information Updated By ADMIN"
     * }
     */
    public function CONVERTAgentAdmin(Request $request)
    {

        $agent = $request->query('id');

        if(!$agent)
        {
            return response()->json(['success'=>0, 'error' => 'Please provide the associate id'], 400);
        }
        $agentInfo=AgentRegister::where('id',$agent)->first();

        if(!$agentInfo)
        {
            return response()->json(['success'=>0, 'error' => 'No information found regarding this associate id'], 404); 
        }

        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string',
            'pancard_no' => [
                Rule::requiredIf($request->fullname !== 'MVG'), 
                'string', 
                'min:10', 
                'max:10'
            ],
            'aadhaar_card' => [
                Rule::requiredIf($request->fullname !== 'MVG'), 
                'string', 
                'min:12', 
                'max:12'
            ],
            'contact_no' => [
                Rule::requiredIf($request->fullname !== 'MVG'), 
                'string', 
                'min:10', 
                'max:10'
            ],
            'email' => [
                Rule::requiredIf($request->fullname !== 'MVG'), 
                'string', 
                'email'
            ],
            'password' => [
                Rule::requiredIf($request->fullname !== 'MVG'),
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/'
            ]
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

        $data=$validator->validated();

        if ($data['fullname'] === 'MVG') {
            $data = [
                'fullname' => 'MVG',
                'pancard_no' => $request->input('pancard_no', '1000000001'),
                'aadhaar_card' => $request->input('aadhaar_card', 'MVG AADHAAR '),
                'contact_no' => $request->input('contact_no', 'MVGCONTACT'),
                'email' => $request->input('email', 'MVG@gmail.com'),
                'password' => Hash::make($request->input('password', 'MVGMASTER2key'))
            ];
        } else {
            $data['password'] = Hash::make($data['password']);
        }
    
        $agentInfo->update($data);
    
        if($agentInfo->fullname != 'MVG')
        {
            return response()->json(['success'=>1, 'data' => $agentInfo,'message' => "NEW Associate {$agentInfo->fullname} is Registered By ADMIN at Referral Code {$agentInfo->referral_code}."], 201);
        }
        return response()->json(['success'=>1, 'data' => $agentInfo,'message' => 'Associate Information Updated By ADMIN, ASSOCIATE ID is overtaken by MVG'], 201);            
    }
}

// php artisan make:migration add_columns_in_client_e_m_i_infos_table --table=client_e_m_i_infos