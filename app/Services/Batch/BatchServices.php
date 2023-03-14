<?php
namespace App\Services\Batch;

use App\Helpers\Utils;
use App\Models\sync_Payments as Payments;
use App\Services\DataServiceHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Facades\Payment;


//session_start();
class BatchServices {
    protected $dataService;
    protected $data;
    protected $payload_ids = [];
    protected $response_ids = [];
    protected  $batch;
    public function __construct($request){
        $this->data["user_id"] = 12;// Utils::getApiUser($request);
        $dataService = new DataServiceHelper($this->data);

        $this->dataService = $dataService->getDataService();

    }


    public function index(){


        return  $this->dataService->Query("SELECT * FROM Payment ");
    }

    public function storeBatch(){
        $status = [1,3];
        foreach($status as $state){
            $this->batch = $this->dataService->CreateNewBatch();

            DB::table('sync_payments')->where('status', $state)
            ->where('qb_id', 0)
            ->orderBy('payment_id', 'asc')
            ->select('account_name','reference_number','date_time','amount','mobile_number','line_items','notes','payment_id')
            ->chunk(30, function ( $payments) {
                foreach ($payments as $payment) {
                    $this->payload_ids[] = $payment->payment_id;
                        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$payment->account_name' ");
                        if(!$customer){
                            print_r("null");
                        // return ["message" => "Account number $name Not Found", "code" => 404];
                        }

                    /*  $payload = [
                            "CustomerRef"=>
                            [
                                "value" =>$customer[0]->Id,
                                "name" =>$payment->account_name,
                            ],
                            "Line" => $payment->line_items,
                            "TotalAmt" => $payment->amount,
                            "PaymentRefNum" => $payment->reference_number,
                            "TxnDate" => $payment->date_time,
                            "PrivateNote" => $payment->notes,
                            "CustomField" => $payment->mobile_number,
                        ];
                        $payload = Payment::create($payload); */

                        $payload = $this->processPayment($payment, $customer);

                        $this->batch->AddEntity($payload, $payment->payment_id, "Create");

                }

                $this->batch->ExecuteWithRequestID($this->generateRandomString());

                $error = $this->batch->getLastError();
                if ($error) {
                    /*if($state == 5){
                    } */
                   /* DB::table('sync_payments')
                    ->where('payment_id',$payment->payment_id )
                    ->update([
                    'status' => 5,
                    'response_message' => $error,
                    'line_items' =>json_encode( $payload->Line, true),
                    //'response' => json_encode($response, true),
                ]); */
                }
                else{
                    foreach($this->batch->intuitBatchItemResponses as $batchItemResponse){
                        $this->response_ids[] = $batchItemResponse->batchItemId;

                        $response['Id'] = $batchItemResponse->entity->Id;
                        $response['SyncToken'] = $batchItemResponse->entity->SyncToken;
                        $response['CreatedDate'] = $batchItemResponse->entity->MetaData->CreateTime;
                        $response['UnappliedAmt'] = $batchItemResponse->entity->UnappliedAmt;

                      /*  DB::table('sync_payments')
                        ->where('payment_id', $batchItemResponse->batchItemId)
                        ->update([
                        'status' => 2,
                        'qb_id' => $batchItemResponse->entity->Id,
                        'response_message' => $error,
                        //'qb_id' => $response["Id"] ?? 0,
                        'line_items' =>json_encode( $payload->Line, true),
                        'response' => json_encode($response, true),
                    ]); */

                    Log::info("LogPaymentInBatchPayment | payment created response |Request->".json_encode($this->data)."|Response =>".json_encode($batchItemResponse));

                    }
                }

                Log::info($batchItemResponse->batchItemId);
            });
            //Log::info("LogPaymentInBatchPayment | payment created response |Request->".json_encode($this->data)."|Response =>".json_encode($batchItemResponse));
        }

        //Failed Payments
        $this->payload_ids = array_diff($this->payload_ids, $this->response_ids);
        print_r($this->payload_ids);

    }


    public function processPayment($data, $customer){
        $id = $customer[0]->Id;
        $invoices = $this->dataService->Query("SELECT * FROM Invoice WHERE CustomerRef = '$id' and Balance > '0' ");
        $lineItems = [];


        $payment_amount = $data->amount;
		//$paid_amount = $payment_amount;
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
                    "value" =>$customer[0]->Id,
                    "name" =>$data->account_name,
                ],
                "Line" => $lineItems,
                "TotalAmt" => $data->amount,
                "PaymentRefNum" => $data->reference_number,
                "TxnDate" => $data->date_time,
                "PrivateNote" => $data->notes,
                "CustomField" => $data->mobile_number,
            ];

			return Payment::create($payload);

            //$data["line"] = $lineItems;


           // Log::info("LogPayment | payment request payload created ".json_encode($payload));

		} catch (\Throwable $th) {
			Log::Error("LogPayment|Error".json_encode($payload)."|Error Response =>".$th);
          // return ["status" => false, "message" => $th->getMessage(), "code" => 422];
        }
     }

    public function storeBatchTest(){

        $theResourceObj = Invoice::create([
            "Line" => [
        [
            "Amount" => 44.00,
            "DetailType" => "SalesItemLineDetail",
            "SalesItemLineDetail" => [
            "ItemRef" => [
                "value" => 1,
                "name" => "Services"
            ]
            ]
            ]
        ],
        "CustomerRef"=> [
            "value"=> 204
        ]
        ]);
        $theResourceObj2 = Invoice::create([
            "Line" => [
        [
            "Amount" => 33.00,
            "DetailType" => "SalesItemLineDetail",
            "SalesItemLineDetail" => [
            "ItemRef" => [
                "value" => 1,
                "name" => "Services"
            ]
            ]
            ]
        ],
        "CustomerRef"=> [
            "value"=> 204
        ]
        ]);
        // Run a batch
        $batch = $this->dataService->CreateNewBatch();
        $batch->AddQuery("select * from Customer startPosition 0 maxResults 5", "queryCustomer");
        $batch->AddQuery("select * from Vendor startPosition 0 maxResults 5", "queryVendor");
        $batch->AddEntity($theResourceObj, "CreateInvoice", "Create");
        $batch->AddEntity($theResourceObj2, "CreateInvoicej", "Createj");
        $batch->ExecuteWithRequestID($this->generateRandomString());
        $error = $batch->getLastError();
        if ($error) {
      /*  echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
        echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
        echo "The Response message is: " . $error->getResponseBody() . "\n"; */
        return ["message" => $error->getResponseBody(), "status" => $error->getHttpStatusCode()];
        //exit();
        }

        // Echo some formatted output
        $batchItemResponse_createInvoice = $batch->intuitBatchItemResponses["CreateInvoice"];
        if($batchItemResponse_createInvoice->isSuccess()){
        $createdInvoice = $batchItemResponse_createInvoice->getResult();
        echo "Create Invoice success!:\n";
        //$xmlBody = XmlObjectSerializer::getPostXmlFromArbitraryEntity($createdInvoice, $something);
        //echo $xmlBody . "\n";

        print_r($createdInvoice);
        }else{
        $result = $batchItemResponse_createInvoice->getError();
        var_dump($result);
        }

        $batchItemResponse_queryCustomer = $batch->intuitBatchItemResponses["queryCustomer"];
        if($batchItemResponse_queryCustomer->isSuccess()){
        $customers = $batchItemResponse_queryCustomer->getResult();
        echo "Query success!:\n";
        print_r($customers);

        foreach($customers as $oneCustomer){
            //$xmlBody = XmlObjectSerializer::getPostXmlFromArbitraryEntity($oneCustomer, $something);
            //echo $xmlBody . "\n";
        }

        }else{
        $result = $batchItemResponse_queryCustomer->getError();
        var_dump($result);
        }

        $batchItemResponse_queryVendor = $batch->intuitBatchItemResponses["queryVendor"];
        if($batchItemResponse_queryVendor->isSuccess()){
        echo "Query success!:\n";
        $vendors = $batchItemResponse_queryVendor->getResult();
        print_r($vendors);
        foreach($vendors as $oneVendor){
          //  $xmlBody = XmlObjectSerializer::getPostXmlFromArbitraryEntity($oneVendor , $something);
            //echo $xmlBody . "\n";
        }

        }else{
        $result = $batchItemResponse_queryVendor->getError();
        var_dump($result);
        }

        //$batchItemResponse = $batch->intuitBatchItemResponses[1];
        //echo "Looked for up to {$maxSearch} vendors; found " . count($batchItemResponse->entities) . "\n";

        /*
        Example output:

        Looked for up to 500 customers; found 318
        Looked for up to 500 vendors; found 278
        */

            }



     public static function generateRandomString($length = 10): string
     {
         $original_string = array_merge(range(0, 29), range('a', 'z'), range('A', 'Z'));
         $original_string = implode("", $original_string);
         return substr(str_shuffle($original_string), 0, $length);
     }

}
