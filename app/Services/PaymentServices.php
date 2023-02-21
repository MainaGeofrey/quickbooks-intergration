<?php
namespace App\Services;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Payment;


//session_start();
class PaymentServices {

    protected $dataService;
    public function __construct(){
        $dataService = new DataServiceHelper();
        $this->dataService = $dataService->getDataService();
    }
    public function store(){
        $payment = Payment::create([
            "TotalAmt"=> 200,
            "CustomerRef" => [
              "value" => 69
            ],
        ]);

        $result = $this->dataService->Add($payment);
        $result = json_encode($result, JSON_PRETTY_PRINT);
        print_r($result);
    }

    public function show(){
        $result = $this->dataService->Query("SELECT * FROM Payment WHERE DisplayName = 'Student456'");
        $result = json_encode($result, JSON_PRETTY_PRINT);
        print_r($result);
     }
}
