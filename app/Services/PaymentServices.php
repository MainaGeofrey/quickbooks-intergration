<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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
        $validator = Validator::make($data->data, [
            'account_name' => 'required|string',
            //'username' => 'required|unique:users,username,NULL,id,deleted_at,NULL',
            //'email' => 'nullable|email|unique:users,email,NULL,id,deleted_at,NULL',

        ]);

        if($validator->fails()){

            return response()->json(["message" => "Please provide the AccountNumber", "code" => 422]);
        }

        Log::info("LogPayment | payment request  ".__METHOD__."|".json_encode($data->data).json_encode($this->data));
        $name = $data->data["account_name"];
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name'  ");
        if(!$customer){
            return response()->json(["message" => "Account by name $name Not Found", "code" => 404]);
        }
        $id = $customer[0]->Id;
        //TODO query all open invoices
        $invoices = $this->dataService->Query("SELECT * FROM Invoice WHERE CustomerRef = '$id' and Balance > '0' ");
        if(!$invoices){
            return response()->json(["message" => "Error We do not have any invoices to apply this payment", "code" => 404]);
        }
        $data["id"] = $id;
        $data["name"] = $name;

        //Log::info(count($invoices));
        try {
            if($invoices){
                $payment = $this->payInvoices($data, $invoices);
               //$this->paySingleInvoice($data, $invoices);

               $payment = $this->paymentResponse($payment,$name);
               Log::info("LogPayment | payment request created successfully  ".__METHOD__."|".json_encode($payment)."|Payment Created|".json_encode($this->data));

               return response()->json($payment);

            }
            else{
                $payment = Payment::create([
                    "CustomerRef"=>
                    [
                        "value" => $id,
                        "name" => $name,
                    ],
                    "TotalAmt" => $data->data["amount"],
                    "PaymentRefNum" => $data->data["reference_number"],
                    "TxnDate" => $data->data["date_time"],
                    "PrivateNote" => $data->data["remarks"],
                    "CustomField" => $data->data["mobile_number"]

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

                $payment = $this->dataService->Add($payment);

                $payment = $this->paymentResponse($payment,$name);
                Log::info("LoPayment | payment request created successfully  ".__METHOD__."|".json_encode($payment)."|Payment Created|".json_encode($this->data));

                return response()->json($payment);
            }
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function show($data){
        $name = $data->data["AccountNumber"];
        if( $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ")){
            $payments =  $this->dataService->Query("SELECT * FROM Payment WHERE DisplayName = $name");
            if ($payments) {

                return $payments;
            }
            else{
                return response()->json(["message" => "No Payment found for account $name", "code" => 404]);
            }
        }
        else{
            return response()->json(["message" => "Account by name $name Not Found", "code" => 404]);
        }

    }

    public function paymentResponse($data, $name){
        $payment = [];
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ");

        //$payment["PaymentId"] = $data->Id;
        $payment["account_number"] = $name;
        $payment["reference_number"] = $data->PaymentRefNum;
        $payment["mobile_number"] = $data->CustomField;
        $payment["amount"] = $data->TotalAmt;
        $payment["mobile_number"] = $data->CustomField;
        $payment["payer_transaction_id"] = "";
        $payment["remarks"] = $data->PrivateNote;
        $payment["date_time"] = $data->TxnDate;
       // $payment["remarks"] = $data->PrivateNote;
        //$payment["CustomerBalance"] = $customer[0]->Balance;

        return $payment;
    }

     public function payInvoices($data,$invoices){
        //$invoices = $this->invoiceServices->show($data);
        //$invoices = json_decode($invoices, true);
        $lineItems = [];



        $payment_amount = $data->data["amount"];
		$paid_amount = $payment_amount;

		foreach ($invoices as $key =>$invoice) {
            $payment_amount_for_invoice = min($payment_amount, $invoice->Balance); // make sure payment doesn't exceed amount due
                $lineItems[] = [
                                "Amount"=> $payment_amount_for_invoice,
                                "LinkedTxn" => [
                                [
                                    "TxnId" => $invoice->Id,
                                    "TxnType"=> "Invoice"
                                ]]
                            ];
            $payment_amount -= $payment_amount_for_invoice;
            if ($payment_amount <= 0) {
                break;
            }
        }

        $payment = Payment::create([
            "CustomerRef"=>
            [
                "value" => $data["id"],
                "name" => $data["name"],
            ],
            "Line" => $lineItems,
            "TotalAmt" => $data->data["amount"],
            "PaymentRefNum" => $data->data["reference_number"],
            "TxnDate" => $data->data["date_time"],
            "PrivateNote" => $data->data["remarks"],
            "CustomField" => $data->data["mobile_number"]
        ]);



        Log::info(count($lineItems));


     /*  if(count($invoices) > 1){
        $str = $this->generateRandomString();
        $batch = $this->dataService->CreateNewBatch();
        $batch->AddEntity($payment,$str, "Create");
        $batch->ExecuteWithRequestID("ThisIsMyFirstBatchRequest");

        return $payment;
       }  */
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
