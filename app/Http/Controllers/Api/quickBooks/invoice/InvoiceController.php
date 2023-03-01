<?php

namespace App\Http\Controllers\Api\quickBooks\invoice;

use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\InvoiceServices;

class InvoiceController extends Controller
{
    //
    protected $invoice;
    protected $data;

    public function __construct(Request $request){
        $this->data["user_id"] = Utils::getApiUser($request);
        $this->invoice = new InvoiceServices($this->data);
    }

    public function index(){
        $data = $this->invoice->index();

        return response()->json($data);
    }
    public function store(Request $request){
        $data = $this->invoice->store($request);

        return response()->json($data);
    }

    public function show(Request $request){
        $data = $this->invoice->show($request);

        return response()->json($data);
    }


}
