<?php

namespace App\Http\Controllers\Api\quickBooks\balance;

use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BalanceServices;
class BalanceController extends Controller
{
    //
    protected $balance;
    protected $data;



    public function index(request $request){
        $balance = new BalanceServices($request);
        $data = $balance->index();

        return response()->json($data);
    }


    public function show(Request $request){
        $balance = new BalanceServices($request);
        $data = $balance->show($request);


        return response()->json($data);
    }
}
