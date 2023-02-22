<?php

namespace App\Http\Controllers\Api\quickBooks\customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CustomerServices;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    //
    protected $customer;

    public function __construct(){
        $this->customer = new CustomerServices();
    }
    public function store(Request $request){
        $this->customer->store();
    }

    public function show(Request $request){
        $this->customer->show($request);
    }
}
