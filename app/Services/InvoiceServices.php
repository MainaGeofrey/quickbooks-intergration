<?php
namespace App\Services;
use App\Services\DataServiceHelper;
use Illuminate\Support\Facades\Log;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Facades\Payment;

//session_start();
class InvoiceServices {

    protected $dataService;
    public function __construct(){
        $dataService = new DataServiceHelper();
        $this->dataService = $dataService->getDataService();
    }

    public function index(){


        return  $this->dataService->Query("SELECT * FROM Invoice ");
    }
    public function store($data){
        $payment = Invoice::create([
            "Line" => $data->data['Line'],
                "CustomerRef" => [
                  "value" => $data->data["CustomerRef"]["Id"],
                  //"name" => $data->data["CustomerRef"]["DisplayName"]
                ]
        ]);

        $result = $this->dataService->Add($payment);
       // $result = json_encode($result, JSON_PRETTY_PRINT);
       // print_r($result);

        return $result;
    }


    public function show($data){
        //Query Open Invoices
        $result = $this->dataService->Query("SELECT * FROM Invoice WHERE CustomerRef = '$data->id' ");
        //$result = json_encode($result, JSON_PRETTY_PRINT);
        //print_r($result);

        return $result;
     }
}
