<?php

namespace App\Http\Controllers\Api\quickBooks\vendor;

use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VendorServices;
use Illuminate\Support\Facades\Log;

class VendorController extends Controller
{
    //
    protected $vendor;
    protected $data;

    public function index(Request $request){
        $vendor = new VendorServices($request);
        $data = $vendor->index();

        return response()->json($data);
    }
    public function store(Request $request){
        $vendor = new VendorServices($request);
        $data = $vendor->store($request);

        return response()->json($data);
    }

    public function show(Request $request){
        $vendor = new VendorServices($request);
        $data = $vendor->show($request);


        return response()->json($data);
    }
}
