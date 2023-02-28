<?php

namespace App\Http\Controllers\Api\quickBooks\customer;

use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CustomerServices;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    //
    protected $customer;
    protected $data;

    public function __construct(Request $request){
        $this->data["user_id"] = Utils::getApiUser($request);
        $this->customer = new CustomerServices($this->data);
    }

    public function index(){
        $data = $this->customer->index();

        return response()->json($data);
    }
    public function store(Request $request){
        $data = $this->customer->store($request);

        return response()->json($data);
    }

    public function show(Request $request){
        $data = $this->customer->show($request);


        return response()->json($data);
    }
}
