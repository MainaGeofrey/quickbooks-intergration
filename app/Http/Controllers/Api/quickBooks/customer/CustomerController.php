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

    public function index(Request $request){
        $customer = new CustomerServices($request);
        $data = $customer->index();

        return response()->json($data);
    }
    public function store(Request $request){
        $customer = new CustomerServices($request);
        $data = $customer->store($request);

        return response()->json($data);
    }

    public function show(Request $request){
        $customer = new CustomerServices($request);
        $data = $customer->show($request);


        return response()->json($data);
    }
}
