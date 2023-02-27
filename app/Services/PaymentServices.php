<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Payment;


//session_start();
class PaymentServices {
    protected $dataService;
    protected $data;
    public function __construct($data){
        $this->data = $data;
        $dataService = new DataServiceHelper($this->data);

        $this->dataService = $dataService->getDataService();
    }


    public function index(){


        return  $this->dataService->Query("SELECT * FROM Payment ");
    }
    public function store($data){
        $name = $data->data["AccountName"];
        Log::info($name);
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ");
        $id = $customer[0]->Id;
        //TODO query all open invoices
        $invoices = $this->dataService->Query("SELECT * FROM Invoice WHERE CustomerRef = '$id' ");
        Log::info($invoices);
        $data["id"] = $id;

        //Log::info(count($invoices));
        try {
            if($invoices){
                $payment = $this->payInvoices($data, $invoices);
               //$this->paySingleInvoice($data, $invoices);

               return response()->json($payment);

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

                return $this->dataService->Add($payment);
            }
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function show($data){
        $name = $data->data["AccountName"];
        if( $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ")){
            $payments =  $this->dataService->Query("SELECT * FROM Payment WHERE DisplayName = $name");
            if ($payments) {

                return $payments;
            }
            else{
                return response()->json(["message" => "No Payment found"]);
            }
        }
        else{
            return response()->json(["message" => "Account by name . $name  . Not Found"]);
        }

    }

     public function payInvoices($data,$invoices){
        //$invoices = $this->invoiceServices->show($data);
        //$invoices = json_decode($invoices, true);
        $lineItems = [];
        foreach($invoices as $invoice){
            //print_r($invoice->Id);
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
                    "value" => $data["id"],
                    //"name" => $data->data["DisplayName"],
                ],
                "TotalAmt" => $data->data["TotalAmt"],
                "Line" => $lineItem
            ]);

        }

       // Log::info(count($lineItems));


       if(count($invoices) > 1){
        $str = $this->generateRandomString();
        $batch = $this->dataService->CreateNewBatch();
        $batch->AddEntity($payment,$str, "Create");
        $batch->ExecuteWithRequestID("ThisIsMyFirstBatchRequest");

        return $payment;
       }
        //TODO make payments in batches instead of one at a time

        return $this->dataService->Add($payment);

     }


     public static function generateRandomString($length = 10): string
     {
         $original_string = array_merge(range(0, 29), range('a', 'z'), range('A', 'Z'));
         $original_string = implode("", $original_string);
         return substr(str_shuffle($original_string), 0, $length);
     }

}
