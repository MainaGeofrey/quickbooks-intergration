<?php

namespace App\Http\Controllers\Api\quickBooks\invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\InvoiceServices;

class InvoiceController extends Controller
{
    //
    protected $invoice;
    public function __construct(){
        $this->invoice = new InvoiceServices();
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
