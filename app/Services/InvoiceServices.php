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
    protected $data;
    public function __construct($data){
        $this->data = $data;
        $dataService = new DataServiceHelper($this->data);

        $this->dataService = $dataService->getDataService();
    }

    public function index(){


        return  $this->dataService->Query("SELECT * FROM Invoice ");
    }
    public function store($data){
        $name = $data->data["AccountName"];
        Log::info($name);
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ");
        $id = $customer[0]->Id;

        $payment = Invoice::create([
            "Line" => $data->data['Line'],
                "CustomerRef" => [
                  "value" => $id,
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
