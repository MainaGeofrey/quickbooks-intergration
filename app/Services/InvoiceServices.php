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
    public function store($data){
        $payment = Invoice::create([
            "Line" => [
                [
                  "Description" => "Sewing Service for Alex",
                  "Amount" => 150.00,
                  "DetailType" => "SalesItemLineDetail",
                  "SalesItemLineDetail" => [
                    "ItemRef" => [
                      "value" => 32,
                      "name" => "Services"
                    ]
                  ]
                ],
                [
                  "Description" => "Discount for Alex",
                  "Amount" => -10.00,
                  "DetailType" => "SalesItemLineDetail",
                  "SalesItemLineDetail" => [
                    "ItemRef" => [
                      "value" => 42,
                      "name" => "Services"
                    ]
                  ]
                ]
                    ],
                "CustomerRef" => [
                  "value" => "76",
                  "name" => "Student100"
                ]
        ]);

        $result = $this->dataService->Add($payment);
        $result = json_encode($result, JSON_PRETTY_PRINT);
        print_r($result);
    }


    public function show($data){
        $result = $this->dataService->Query("SELECT * FROM Invoice WHERE CustomerRef = '$data->id' ");
        $result = json_encode($result, JSON_PRETTY_PRINT);
        print_r($result);

        return $result;
     }
}
