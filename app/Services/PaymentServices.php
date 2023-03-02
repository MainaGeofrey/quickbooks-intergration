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
        $validator = Validator::make($data->all(), [
            'account_name' => 'required|string',
            'reference_number' => 'required|string',
            'date_time' => 'required|string',
            'amount' =>  'required|numeric|gt:0'
            //'mobile_number' => 'required|string',
            //'username' => 'required|unique:users,username,NULL,id,deleted_at,NULL',
            //'email' => 'nullable|email|unique:users,email,NULL,id,deleted_at,NULL',

        ]);

        if($validator->fails()){

            return ["status"=>false,"message" => $validator->errors()->getMessages(), "code" => 422];
        }

        Log::info("LogPayment | payment request  ".__METHOD__."|".json_encode($data).json_encode($this->data));
        $name = $data["account_name"];
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ");

        if(!$customer){
            return ["status"=>false,"message" => "Account by name $name Not Found", "code" => 404];
        }
        $id = $customer[0]->Id;
        //TODO query all open invoices
        $invoices = $this->dataService->Query("SELECT * FROM Invoice WHERE CustomerRef = '$id' and Balance > '0' ");
        if(!$invoices){
            return ["status"=>false,"message" => "Error We do not have any invoices to apply this payment", "code" => 404];
        }
        $data["id"] = $id;
        $data["name"] = $name;

        //Log::info(count($invoices));
        try {
            if($invoices){
                $payment_response = $this->payInvoices($data, $invoices);
               Log::info("LogPayment | payment created response |Request->".json_encode($this->data)."|Response =>".json_encode($payment_response));
               return $payment_response;

            }
            else{
/*                $payment = Payment::create([
                    "CustomerRef"=>
                    [
                        "value" => $id,
                        "name" => $name,
                    ],
                    "TotalAmt" => $data["amount"],
                    "PaymentRefNum" => $data["reference_number"],
                    "TxnDate" => $data["date_time"],
                    "PrivateNote" => $data["remarks"],
                    "CustomField" => $data["mobile_number"]
                ]);

                $payment = $this->dataService->Add($payment);

                //$payment = $this->paymentResponse($payment,$name);
                Log::info("LoPayment | payment request created successfully  ".__METHOD__."|".json_encode($payment)."|Payment Created|".json_encode($this->data));

		return ["payment_id" => $payment->Id,"status" =>true, "code" => 200];
 */
		  Log::info("Payment| request=>".json_encode($this->data)."| The User has no Invoice, payments not processed");
		  return ['status'=>false,"message"=> "The Account has no invoice","code"=>404];
            }
	} catch (\Throwable $th) {
      Log::Error("LogPayment|Error".json_encode($this->data)."|Error Response =>".$th->getMessage());
           return ["status" => false, "message" => $th->getMessage(), "code" => 422];
        }

    }

    public function show($data){
        $name = $data["AccountNumber"];
        if( $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ")){
            $payments =  $this->dataService->Query("SELECT * FROM Payment WHERE DisplayName = $name");
            if ($payments) {

                return $payments;
            }
            else{
                return response()->json(["status"=>false,"message" => "No Payment found for account $name", "code" => 404]);
            }
        }
        else{
            return response()->json(["status"=>false,"message" => "Account by name $name Not Found", "code" => 404]);
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



        $payment_amount = $data["amount"];
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
		try{
			$payload = [
                "CustomerRef"=>
                [
                    "value" => $data["id"],
                    "name" => $data["name"],
                ],
                "Line" => $lineItems,
                "TotalAmt" => $data["amount"],
                "PaymentRefNum" => $data["reference_number"],
                "TxnDate" => $data["date_time"],
                "PrivateNote" => $data["remarks"],
                "CustomField" => $data["mobile_number"]
            ];
			$payment = Payment::create($payload);

        Log::info("LogPayment | payment request payload created ".json_encode($payload));

			$response = $this->dataService->Add($payment);
			$error = $this->dataService->getLastError();
			if ($error) {
				Log::info("LogPayment |Error|Request =>".json_encode($payload)."|Error Response".$error->getHttpStatusCode()."|
					".$error->getOAuthHelperError()."|".$error->getResponseBody());
				return ['status'=>false,'message'=>'We have received an Error'.$error->getResponseBody()];
} else {
    # code...
    // Echo some formatted output
    return ['status'=>true,"payment_id"=>$response->Id,"message"=>"Successfully created a payment"];
}
		} catch (\Throwable $th) {
			Log::Error("LogPayment|Error".json_encode($payload)."|Error Response =>".$th->getMessage());
           return ["status" => false, "message" => $th->getMessage(), "code" => 422];
        }


     }


     public static function generateRandomString($length = 10): string
     {
         $original_string = array_merge(range(0, 29), range('a', 'z'), range('A', 'Z'));
         $original_string = implode("", $original_string);
         return substr(str_shuffle($original_string), 0, $length);
     }

}
