<?php
namespace App\Services;

use App\Helpers\Utils;
use App\Models\sync_Payments;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Payment;


//session_start();
class PaymentServices {
    protected $dataService;
    protected $data;
    public function __construct($request){
        $this->data["user_id"] = Utils::getApiUser($request);
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
            'date_time' => 'required|date',
            'amount' =>  'required|numeric|gt:0',
            'remarks' => 'sometimes|string',
            'mobile_number' => 'required|string',

        ]);

        if($validator->fails()){

            return ["status"=>false,"message" => $validator->errors()->getMessages(), "code" => 422];
        }

        $name = $data["account_name"];
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name'  ");
        if(!$customer){
            return ["status"=>false,"message" => "Account number $name Not Found", "code" => 404];
        }
        $id = $customer[0]->Id;
        $invoices = $this->dataService->Query("SELECT * FROM Invoice WHERE CustomerRef = '$id' and Balance > '0' ");

        $data["id"] = $id;
        $data["name"] = $name;

        //Log::info(count($invoices));
        try {

            $payment_response = $this->processPayment($data, $invoices);
            Log::info("LogPayment | payment request created response |Request->".json_encode($this->data)."|Response =>".json_encode($payment_response));

            return $payment_response;

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



     public function processPayment($data,$invoices){
        //$invoices = $this->invoiceServices->show($data);
        //$invoices = json_decode($invoices, true);
        $lineItems = [];



        $payment_amount = $data["amount"];
		$paid_amount = $payment_amount;
        if($invoices)
        {
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
        }
		try{
			$payload = [
                "CustomerRef"=>
                [
                    "value" =>$data['id'],
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

            $data["line"] = $lineItems;


            Log::info("LogPayment | payment request payload created ".json_encode($payload));

			$response = $this->dataService->Add($payment);
			$error = $this->dataService->getLastError();
			if ($error) {
                $data['status'] = 5;
                $this->storePayment($data,$response = null ,$error->getIntuitErrorDetail());

				Log::info("LogPayment |Error|Request =>".json_encode($payload)."|Error Response".$error->getHttpStatusCode()."|
					".$error->getOAuthHelperError()."|".$error->getResponseBody());

                return ['status'=>false,'message'=>'We have received an Error'.$error->getIntuitErrorDetail(),'code'=>$error->getHttpStatusCode()];
            } else  if ($response) {
                $response_data['Id'] = $response->Id;
                $response_data['SyncToken'] = $response->Id;
                $response_data['CreatedDate'] = $response->MetaData->CreateTime;
                $response_data['UnappliedAmt'] = $response->UnappliedAmt;
                //$data["response"]['UnappliedAmt'] = $response->UnappliedAmt;


                $data['Id'] = $response->Id;
                $data['status'] = 2; // success, happy path
                $this->storePayment($data,$response_data, $error = null);
                return ['status'=>true,"payment_id"=>$response->Id,"message"=>"Successfully created a payment.".(isset($invoices)?"Invoices updated":"created as a sales receipt"), "code" => 200];
            }
            else{
                ///No error and no response
                //could have failed or succeeded but no error or response
                //TODO before re-push check if payment  created
                $data['status'] = 3;
                $this->storePayment($data);
            }
		} catch (\Throwable $th) {
			Log::Error("LogPayment|Error".json_encode($payload)."|Error Response =>".$th);
           return ["status" => false, "message" => $th->getMessage(), "code" => 422];
        }
     }


     public function storePayment($data,$response = null, $error = null){
        return sync_Payments::create([
            'account_name' => $data["account_name"],
            'reference_number' => $data["reference_number"],
            'date_time' => $data["date_time"],
            'amount' => $data["amount"],
            'mobile_number' => $data["mobile_number"],
            'client_id' => $this->data['user_id'],
            'notes' => $data['remarks'],
            //'processed' => true,
            'status' => $data["status"] ?? 0,
            'response_message' => $error,
            'qb_id' => $response["Id"] ?? 0,
            'line_items' =>json_encode( $data["line"], true),
            'response' => json_encode($response, true),
        ]);
     }

}
