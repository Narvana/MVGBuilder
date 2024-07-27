<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plot;
use App\Models\PlotTransaction;
use App\Models\Plot_Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                    return response()->json(['success'=>0,'message'=>"No data Found, in id {$params}"],404);   
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
                return response()->json(['success'=>0,'message'=>"No data Found, in id {$params}"],404);
            }
            $plot->delete();
            return response()->json(['success'=>1,'message'=>'Plot Removed'],200);
        }catch (\Throwable $th) {
            return response()->json(['success'=>0,'message' => 'Something went wrong', 'details' => $th->getMessage()], 500);
        }
    }

    public function PlotTransaction(Request $request)
    {
        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'plot_sale_id' => 'required|integer',
                'amount' => 'required|integer|min:0',
                'payment_method' => 'required|string'
            ]);
        
            if ($validator->fails()) {
                $errors = $validator->errors()->all(); // Get all error messages
                return response()->json([
                    'success' => 0,
                    'errors' => $errors
                ], 422);
            }
        
            $data = $validator->validated();
        
            $plot_sale = Plot_Sale::findOrFail($data['plot_sale_id']);

            $plot = Plot::where('id',$plot_sale->plot_id)->first();

            if ($plot_sale->plot_status === 'COMPLETED') 
            {
                return response()->json([
                    'success' => 0,
                    'message' => 'No Payment Pending'
                ], 200);
            }
            else
            {
                $transaction_id = 'MVG' . Str::random(10);
        
                // Check if there's an existing transaction
                $existingTransaction = PlotTransaction::where('plot_sale_id', $data['plot_sale_id'])->exists();
            
                // If no existing transaction, validate amount and create a new transaction
                if (!$existingTransaction) {
                    if ($data['amount'] > $plot_sale->totalAmount) {
                        return response()->json([
                            'success' => 0,
                            'message' => "Amount should not be greater than {$plot_sale->totalAmount}"
                        ], 400);
                    }
            
                    $newTransaction = PlotTransaction::create([
                        'plot_sale_id' => $data['plot_sale_id'],
                        'transaction_id' => $transaction_id,
                        'amount' => $data['amount'],
                        'payment_method' => $data['payment_method']
                    ]);
                } else {
                    // Validate payment amount if there's already an existing transaction
                    $amountPaid = PlotTransaction::where('plot_sale_id', $data['plot_sale_id'])
                        ->sum(DB::raw('CAST(amount AS UNSIGNED)'));
            
                    $remainingAmount = $plot_sale->totalAmount - $amountPaid;
            
                    if ($data['amount'] > $remainingAmount) {
                        return response()->json([
                            'success' => 0,
                            'message' => "Amount paid so far: {$amountPaid}. Payment should not be greater than {$remainingAmount}"
                        ], 400);
                    }
            
                    // Create a new transaction record
                    $newTransaction = PlotTransaction::create([
                        'plot_sale_id' => $data['plot_sale_id'],
                        'transaction_id' => $transaction_id,
                        'amount' => $data['amount'],
                        'payment_method' => $data['payment_method']
                    ]);
                }
            
                // Calculate the total amount paid and determine the plot status
                $amountPaid = PlotTransaction::where('plot_sale_id', $data['plot_sale_id'])
                    ->sum(DB::raw('CAST(amount AS UNSIGNED)'));
            
                $percentagePaid = round(($amountPaid / $plot_sale->totalAmount) * 100, 2);
            
                $status = $percentagePaid < 30 ? 'PENDING' :
                          ($percentagePaid < 100 ? 'BOOKED' : 'COMPLETED');
            
                // Update plot sale and plot status
                $plot_sale->update([
                    'plot_status' => $status,
                    'plot_value' => $percentagePaid
                ]);
            
                $plot->update([
                    'plot_status' => $status
                ]);
            
                return response()->json([
                    'success' => 1,
                    'message' => 'Transaction Added',
                    'transaction' => $newTransaction,
                    'plot_sale' => $plot_sale,
                    'plot' => $plot
                ], 201);        
            } 

        } catch (\Throwable $th) {
            return response()->json([
                'success' => 0,
                'message' => 'Something went wrong',
                'details' => $th->getMessage()
            ], 500);
        }        
    }

    public function showPlotSales(Request $request)
{
    $sales = DB::table('plot_sales')->get();
    return response()->json(['success'=>1 ,'sales'=>$sales]);
}

}


