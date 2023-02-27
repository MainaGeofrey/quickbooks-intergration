<?php

namespace App\Http\Controllers\Api\quickBooks\payment;

use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use App\Services\PaymentServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    //
    protected $payment;
    protected $data;

    public function __construct(Request $request){
        $this->data["user_id"] = Utils::getApiUser($request);
        $this->payment = new PaymentServices($this->data);
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
