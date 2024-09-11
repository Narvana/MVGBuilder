<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SiteController extends Controller
{
    //
    public function addSite(Request $request)
    {
        try {
            //code...
            $params=$request->query('id');
            $site = Site::find($params);
            $validator=Validator::make($request->all(),[
                'site_name' => $site ? 'nullable|string|unique:sites,site_name' :'required|string|unique:sites,site_name',
                'site_areas' => $site ? 'nullable|array': 'required|array',
                'site_areas.*' => 'nullable|numeric',
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
            $data=$validator -> validated();

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
                    'error' => 'Site not found',
                ], 404);
            }
            $newSite=Site::create($data);
            return response()->json([
                'success'=>1, 
                'message' => 'Site created successfully', 
                'site' => $newSite
            ], 201);

        } catch (\Throwable $th) {
                return response()->json(['success'=>0, 'error' =>'Internal Server Error. '. $th->getMessage()], 500);
            }
        }

 
        public function showSite(Request $request) {
        try {
            //code...
            $sites=Site::get();
            $params=$request->query('id');
            if($sites->isEmpty())
            {
                return response()->json(['success'=>0,'error'=>'No Data Exists or No data Found'],404);
            }
            if($params){
                $site = Site::find($params);  
                if(!$site)
                {
                    return response()->json(['success'=>0,'error'=>"No data Found, in id {$params}"],404);   
                }
                return response()->json(['success'=>1,'site'=>$site],200);
            }
            return response()->json(['success'=>1,'sites'=>$sites],200);
        } catch (\Throwable $th) {
            return response()->json(['success'=>0, 'error' => 'Internal Server Error. ' . $th->getMessage()], 500);
        }
    }

    public function showPlotArea(Request $request)
    {
        $areas=Site::pluck('site_areas');
        // return response()->json(['success'=>1,'areas'=>$area],200);
        $uniqueAreas = $areas->flatMap(function ($siteAreas) {
            // Decode JSON string to array
            return json_decode($siteAreas, true);
        })->unique()->sort()->values();
    
        // Return the result in JSON format
        return response()->json([
            'success' => 1,
            'areas' => $uniqueAreas
        ], 200);
    }

    public function removeSite(Request $request)
    {
        try {
            //code...
            $params=$request->query('id');
            $sites=Site::where('id',$params)->first();
            if(!$sites)
            {
                return response()->json(['success'=>0,'error'=>"No data Found, in id {$params}"],404);
            }
            $sites->delete();
            return response()->json(['success'=>1,'message'=>'Site Removed'],200);
        }catch (\Throwable $th) {
            return response()->json(['success'=>0, 'details' => 'Internal Server Error. ' . $th->getMessage()], 500);
        }
    }
}
