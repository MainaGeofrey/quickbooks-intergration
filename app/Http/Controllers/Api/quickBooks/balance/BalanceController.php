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

    public function __construct(Request $request){
        $this->data["user_id"] = Utils::getApiUser($request);
        $this->balance = new BalanceServices($this->data);
    }

    public function index(){
        $data = $this->balance->index();

        return response()->json($data);
    }


    public function show(Request $request){
        $data = $this->balance->show($request);


        return response()->json($data);
    }
}
