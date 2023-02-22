<?php

namespace App\Http\Controllers\Api\quickBooks\payment;

use App\Http\Controllers\Controller;
use App\Services\PaymentServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    //
    protected $payment;

    public function __construct(){
        $this->payment = new PaymentServices();
    }
    public function store(Request $request){
        try {
            $this->payment->store();
        } catch (\Throwable $th) {
            //throw $th;
            //Log::error($th->getMessage());
        }
    }


    public function batchPay(Request $request){
        try {
            $this->payment->payBatch($request);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
