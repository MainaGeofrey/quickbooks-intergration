<?php
namespace App\Services;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Payment;



//session_start();
class ItemServices {

    protected $dataService;
    public function __construct(){
        $dataService = new DataServiceHelper();
        $this->dataService = $dataService->getDataService();
    }
    public function store(){
        $payment = Payment::create([
            "TotalAmt"=> 22200000.00,
            "CustomerRef" => [
              "value" => 66
            ],
        ]);

        $result = $this->dataService->Add($payment);
        $result = json_encode($result, JSON_PRETTY_PRINT);
        print_r($result);
    }
}
