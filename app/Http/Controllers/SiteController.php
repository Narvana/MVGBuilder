<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SiteController extends Controller
{
    //
    public function addSite(Request $request)
    {
        try {
            //code...
            $validator=Validator::make($request->all(),[
                'site_name' => 'required|string|unique:sites,site_name'
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
            $data=$validator -> validated();
            $params=$request->query('id');
            if ($params) {
                // Find the site by id
                $site = Site::find($params);
        
                if ($site) {
                    // Update the site
                    $site->update($data);
        
                    return response()->json([
                        'success' => 1,
                        'message' => 'Site updated successfully',
                        'site' => $site
                    ], 201);
                } else {
                    return response()->json([
                        'success' => 0,
                        'message' => 'Site not found',
                    ], 404);
                }
            }
            $newSite=Site::create($data);
            return response()->json([
                'success'=>1, 
                'message' => 'Site created successfully', 
                'site' => $newSite
            ], 201);

        } catch (\Throwable $th) {
                return response()->json(['success'=>0,'message' => 'Something went wrong', 'details' => $th->getMessage()], 500);
            }
        }

    public function showSite(Request $request) {
        try {
            //code...
            $sites=Site::get();
            $params=$request->query('id');
            if($sites->isEmpty())
            {
                return response()->json(['success'=>0,'message'=>'No data Found'],404);
            }
            if($params){
                $site = Site::find($params);   
                return response()->json(['success'=>1,'site'=>$site],200);
            }
            return response()->json(['success'=>1,'sites'=>$sites],200);
        } catch (\Throwable $th) {
            return response()->json(['success'=>0,'message' => 'Something went wrong', 'details' => $th->getMessage()], 500);
        }
    }

    public function removeSite(Request $request)
    {
        try {
            //code...
            $params=$request->query('id');
            $sites=Site::where('id',$params)->first();
            if(!$sites)
            {
                return response()->json(['success'=>0,'message'=>'No data Found'],404);
            }
            $sites->delete();
            return response()->json(['success'=>1,'message'=>'Site Removed'],200);
        }catch (\Throwable $th) {
            return response()->json(['success'=>0,'message' => 'Something went wrong', 'details' => $th->getMessage()], 500);
        }
    }
}
