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

    public function index(request $request){
        $bill = new BillServices($request);
        $data = $bill->index();

        return response()->json($data);
    }


    public function store(Request $request){
        $bill = new BillServices($request);
        $data = $bill->store($request);

        return response()->json($data);
    }

}

?>
