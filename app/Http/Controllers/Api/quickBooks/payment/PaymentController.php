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


    public function index(){
        $data = $this->payment->index();

        return response()->json($data);
    }
    public function store(Request $request){
        try {
            $data = $this->payment->store($request);

            return response()->json($data);
        } catch (\Throwable $th) {
            //throw $th;
            //Log::error($th->getMessage());

            return response()->json(["code" => 422, "message" => $th->getMessage()]);
        }
    }



}
