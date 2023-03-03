<?php

namespace App\Http\Controllers\Api\quickBooks\Vendor;

use Illuminate\Http\Request;
use App\Services\VendorServices;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class VendorController extends Controller
{
    //
    protected $vendor;

    public function __construct(){
        $this->vendor = new VendorServices();
    }

    public function index(){
        $data = $this->vendor->index();

        return response()->json($data);
    }

    // public function vendors(Request $request){
    //     $data = $this->bill->vendors($request);

    //     return response()->json($data);
    // }

    public function store(Request $request){
        $data = $this->vendor->store($request);

        return response()->json($data);
    }

    // public function show(Request $request){
    //     $data = $this->bill->show($request);


    //     return response()->json($data);
    // }
}
