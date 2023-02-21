<?php
//namespace App\Services;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Facades\Payment;

include '../../DataServiceHelper.php';

//session_start();
class InvoiceServices {

    protected $dataService;
    public function __construct(){
        $dataService = new DataServiceHelper();
        $this->dataService = $dataService->getDataService();
    }
    public function store(){
        $payment = Invoice::create([
            "Line"=> [
                [
                    "DetailType"=> "SalesItemLineDetail", 
                    "Amount" => 100.0, 
                    "SalesItemLineDetail" => [
                      "ItemRef" => [
                        "name" => "Services", 
                        "value" =>"1"

                      ]
                    ]
                ]
                ], 
                "CustomerRef" => [
                  "value" => "68",
                  "name" => "Student456" 
                ]
        ]);

        $result = $this->dataService->Add($payment);
        $result = json_encode($result, JSON_PRETTY_PRINT);
        print_r($result);
    }


    public function show(){
        $result = $this->dataService->Query("SELECT * FROM Invoice WHERE DisplayName = 'Student456'");
        $result = json_encode($result, JSON_PRETTY_PRINT);
        print_r($result);
     }
}