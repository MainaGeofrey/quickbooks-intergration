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


    public function index(Request $request){
        $invoice = new InvoiceServices($request);
        $data = $invoice->index();

        return response()->json($data);
    }
    public function store(Request $request){
        $invoice = new InvoiceServices($request);
        $data = $invoice->store($request);

        return response()->json($data);
    }

    public function show(Request $request){
        $invoice = new InvoiceServices($request);
        $data = $invoice->show($request);

        return response()->json($data);
    }


}
