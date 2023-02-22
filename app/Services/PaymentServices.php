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
    public function store($data){
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
                        "TxnId" => $data["invoice_id"],
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
            Log::info($invoice["Id"]);
            $data["invoice_id"] = $invoice["Id"];
            $this->store($data);
            print_r($invoice);
            break;
        }

        //TODO make payments in batches instead of one at a time


     }
}
