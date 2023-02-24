<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Payment;


//session_start();
class PaymentServices {

    protected $dataService;
    //protected $invoiceServices;
    public function __construct(){
        $dataService = new DataServiceHelper();
        //$this->invoiceServices = new InvoiceServices();
        $this->dataService = $dataService->getDataService();
    }


    public function index(){


        return  $this->dataService->Query("SELECT * FROM Payment ");
    }
    public function store($data){
        $id = $data->data["CustomerRef"]["Id"];
        //TODO query all open invoices
        $invoices = $this->dataService->Query("SELECT * FROM Invoice WHERE CustomerRef = '$id' ");

        //Log::info(count($invoices));
        try {
            if($invoices){
                $payment = $this->payInvoices($data, $invoices);
               //$payment = $this->paySingleInvoice($data, $invoices);



               // return response()->json($payment);
            }
            else{
            $payment = Payment::create([
                "CustomerRef"=>
                [
                    "value" => $id,
                    //"name" => $data->data["CustomerRef"]["DisplayName"],
                ],
                "TotalAmt" => $data->data["TotalAmt"],
              /*  "Line" => [
                [
                    "Amount"=> 100.00,
                    "LinkedTxn" => [
                    [
                        "TxnId" => $data["invoice_id"],
                        "TxnType"=> "Invoice"
                    ]]
                ]] */
            ]);
            }
        } catch (\Throwable $th) {
            throw $th;
        }


        return $this->dataService->Add($payment);
    }

    public function show(){
        return  $this->dataService->Query("SELECT * FROM Payment WHERE DisplayName = 'Student456'");
     }


    public function paySingleInvoice($data, $invoices){
        foreach($invoices as $invoice){
            $payment = Payment::create([
                "CustomerRef"=>
                [
                    "value" => $data->data["CustomerRef"]["Id"],
                    //"name" => $data->data["CustomerRef"]["DisplayName"],
                ],
                //"TotalAmt" => $data->data["TotalAmt"],
                "Line" => [
                [
                    "Amount"=> $data->data["TotalAmt"],
                    "LinkedTxn" => [
                    [
                        "TxnId" => $invoice->Id,
                        "TxnType"=> "Invoice"
                    ]]
                ]]
            ]);
        }

        return $payment;
    }
     public function payInvoices($data,$invoices){
        //$invoices = $this->invoiceServices->show($data);
        //$invoices = json_decode($invoices, true);
        $lineItems = [];
        foreach($invoices as $invoice){
            print_r($invoice->Id);
            $lineItem =
                [[
                    //TODO pay amount specific to each Invoice//sum of all invoice Line Items
                    "Amount"=> $data->data["TotalAmt"],
                    "LinkedTxn" => [
                    [
                        "TxnId" => $invoice->Id,
                        "TxnType"=> "Invoice"
                    ]]
                ]];
               // array_push($lineItems,$lineItem);
               $payment = Payment::create([
                "CustomerRef"=>
                [
                    "value" => $data->data["CustomerRef"]["Id"],
                    //"name" => $data->data["DisplayName"],
                ],
                "TotalAmt" => $data->data["TotalAmt"],
                "Line" => $lineItem
            ]);

        }

       // Log::info(count($lineItems));

       $str = $this->generateRandomString();
       Log::info($str);

        $batch = $this->dataService->CreateNewBatch();
        $batch->AddEntity($payment,$str, "Create");
        $batch->ExecuteWithRequestID("ThisIsMyFirstBatchRequest");

        //TODO make payments in batches instead of one at a time

        return $payment;

     }


     public static function generateRandomString($length = 10): string
     {
         $original_string = array_merge(range(0, 29), range('a', 'z'), range('A', 'Z'));
         $original_string = implode("", $original_string);
         return substr(str_shuffle($original_string), 0, $length);
     }

}
