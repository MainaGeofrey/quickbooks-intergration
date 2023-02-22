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

    }
    public function store(Request $request){
        $this->invoice->store($request);
    }

    public function show(Request $request){
        $this->invoice->show($request);
    }


}
