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

    public function __construct(Request $request){
        $this->data["user_id"] = Utils::getApiUser($request);
        $this->vendor = new VendorServices($this->data);
    }

    public function index(){
        $data = $this->vendor->index();

        return response()->json($data);
    }
    public function store(Request $request){
        $data = $this->vendor->store($request);

        return response()->json($data);
    }

    public function show(Request $request){
        $data = $this->vendor->show($request);


        return response()->json($data);
    }
}
