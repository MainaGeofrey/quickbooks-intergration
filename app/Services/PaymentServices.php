<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Payment;


//session_start();
class PaymentServices {

    protected $dataService;
    protected $invoiceServices;
    public function __construct(){
        $dataService = new DataServiceHelper();
        $this->invoiceServices = new InvoiceServices();
        $this->dataService = $dataService->getDataService();
    }
    public function store(){
        try {
            $payment = Payment::create([
                "CustomerRef"=>
                [
                    "value" => 73,
                    "name" => "Student100"
                ],
                "TotalAmt" => 100.00,
                "Line" => [
                [
                    "Amount"=> 100.00,
                    "LinkedTxn" => [
                    [
                        "TxnId" => 178,
                        "TxnType"=> "Invoice"
                    ]]
                ]]
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }


        $result = $this->dataService->Add($payment);
        $result = json_encode($result, JSON_PRETTY_PRINT);
        print_r($result);
    }

    public function show(){
        $result = $this->dataService->Query("SELECT * FROM Payment WHERE DisplayName = 'Student456'");
        $result = json_encode($result, JSON_PRETTY_PRINT);
        print_r($result);
     }

     public function payBatch($data){
        $invoices = $this->invoiceServices->show($data);
        $invoices = json_decode($invoices, true);
        foreach($invoices as $invoice){
            Log::info($invoice);
            print_r($invoice);
            break;
        }


     }
}
