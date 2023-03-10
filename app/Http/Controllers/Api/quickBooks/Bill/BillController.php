<?php

namespace App\Http\Controllers\Api\quickBooks\Bill;

use Illuminate\Http\Request;
use App\Services\BillServices;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Helpers\Utils;


class BillController extends Controller
{
    //
    protected $bill;

    protected $data;
    public function __construct(Request $request){
        $this->data["user_id"] = Utils::getApiUser($request);
        $this->bill = new BillServices($this->data);
    }

    public function index(){
        $data = $this->bill->index();

        return response()->json($data);
    }

    // public function vendors(Request $request){
    //     $data = $this->bill->vendors($request);

    //     return response()->json($data);
    // }

    public function store(Request $request){
        $data = $this->bill->store($request);

        return response()->json($data);
    }

 public function show(Request $request){
     $data = $this->bill->show($request);


     return response()->json($data);
 }
}

?>
