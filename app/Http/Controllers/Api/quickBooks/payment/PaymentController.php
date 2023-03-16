<?php

namespace App\Http\Controllers\Api\quickBooks\payment;

use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use App\Services\Batch\BatchServices;
use App\Services\PaymentServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    //
    protected $payment;
    protected $data;


    public function index(Request $request){
        $payment = new PaymentServices($request);
        $data = $payment->index();

        return response()->json($data);
    }
    public function store(Request $request){
        $payment = new PaymentServices($request);
        try {
            $payment_response = $payment->store($request);
	$status =$payment_response['status']??false;

	    if($status ==false)
		    return response()->json($payment_response,$payment_response['code']??400);
	    else
		    return response()->json($payment_response);

        } catch (\Throwable $th) {
            //throw $th;
            //Log::error($th->getMessage());

            return response()->json([ "message" => $th->getMessage(),"status" => false, "code" => 422,]);
        }
    }



}
