<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlotController extends Controller
{
    //
    public function addPlot(Request $request)
    {
        try {
            //code...
            $params=$request->query('id');
            $plot=Plot::where('id',$params)->first();            
            $validator=Validator::make($request->all(),[
                'site_id' => $plot ? 'nullable|integer' : 'required|integer',
                'plot_No' => $plot ? 'nullable|string' : 'required|string',
                'plot_type' =>  $plot ? 'nullable|string' : 'required|string',
                'plot_area' =>  $plot ? 'nullable|string' : 'required|string',
                'price_from' =>  $plot ? 'nullable|integer' : 'required|integer',
                'price_to' =>  $plot ? 'nullable|integer' : 'required|integer',            
                'price_status' => $plot ? 'nullable|string' : 'required|string',
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

            $data=$validator->validated();

            if($plot){
                $plot->update($data);
                return response()->json([
                    'success'=>1,
                    'message' => 'Plot updated successfully',
                    'Plot' => $plot
                ], 201);
            }
            $newPlot=Plot::create($data);
            return response()->json([
                'success'=>1, 
                'message' => 'Plot Added successfully',
                'plot' => $newPlot
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['success'=>0,'message' => 'Something went wrong', 'details' => $th->getMessage()], 500);
        }
    }

    public function showPlot(Request $request)
    {
        try {
            //code...            
            $plots=Plot::get();
            $params=$request->query('id');
            if($plots->isEmpty())
            {
                return response()->json(['success'=>0,'message'=>'No data Found'],404);
            }
            if($params){
                $plot = Plot::find($params);
                if(!$plot)
                {
                    return response()->json(['success'=>0,'message'=>'No data Found, in this id'],404);   
                }
                return response()->json(['success'=>1,'plot'=>$plot],200);
            }
            return response()->json(['success'=>1,'plots'=>$plots],200);
        } catch (\Throwable $th) {
            return response()->json(['success'=>0,'message' => 'Something went wrong', 'details' => $th->getMessage()], 500);
        }
    }

    public function removePlot(Request $request){
        try {
            //code...
            $params=$request->query('id');
// -            $plot=Plot::where('id',$params)->first();
            $plot = Plot::find($params);
            if(!$plot)
            {
                return response()->json(['success'=>0,'message'=>'No data Found'],404);
            }
            $plot->delete();
            return response()->json(['success'=>1,'message'=>'Plot Removed'],200);
        }catch (\Throwable $th) {
            return response()->json(['success'=>0,'message' => 'Something went wrong', 'details' => $th->getMessage()], 500);
        }
    }

}
