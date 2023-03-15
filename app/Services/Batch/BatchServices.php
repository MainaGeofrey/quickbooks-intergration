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

    protected $clients = [];

    protected $distinct;
    public function __invoke(){
        $clients[] =  DB::table('users')
        ->join('sync_payments', 'users.id', '=', 'sync_payments.client_id')
        ->where('users.status', '=', 1)
        ->select('users.id')
        ->distinct()
        ->get()
        ->toArray();

        $this->clients = array_merge(...$clients);

        $this->clients = array_map(function ($client) {
            return $client->id;
        }, $this->clients);


        foreach($this->clients as $client){
            try{
                $this->data["user_id"] = $client;
                $dataService = new DataServiceHelper($this->data);

                $data["dataService"] = $dataService->getValidQBConfig();

                if( $data["dataService"]["code"]== 404){
                    Log::info("LogBatchPaymentFail |CLIENT ID ".json_encode($client)."|QUICKBOOKS AUTHENTICATION FAILURE");
                    continue;
                }
                else{

                    $this->dataService = $dataService->getDataService();
                    $this->storeBatch($client);
                }
            }
            catch(\Exception $e){
                continue;
            }
        }

    }


    public function storeBatch($client){
        $status = [1,3];
        foreach($status as $state){
            $this->batch = $this->dataService->CreateNewBatch();
            $this->distinct = [];


            DB::table('sync_payments')
            ->where('status', $state)
            ->where('qb_id', 0)
            ->where('client_id', $client)
            ->orderBy('payment_id', 'asc')
            //->unique('account_name')
            ->select('client_id','account_name','customer_qb','reference_number','date_time','amount','mobile_number','line_items','notes','payment_id')
            ->chunk(30, function ( $payments) {
                foreach ($payments as $payment) {
                    print_r($this->distinct);

                    //allow only one payment for each customer in batch
                    if(in_array($payment->customer_qb, $this->distinct)){
                        continue;
                    }
                    $this->distinct[] = $payment->customer_qb;
                    print_r($this->distinct);

                    //payments in batch
                    $this->payload_ids[] = $payment->payment_id;


                    $payload = $this->processPayment($payment);

                    $this->batch->AddEntity($payload, $payment->payment_id, "Create");

                }

                $this->batch->ExecuteWithRequestID($this->generateRandomString());

                $error = $this->batch->getLastError();
                if ($error) {
                   /* if($error->getOAuthHelperError()){
                        Log::info("LogBatchPaymentFail |CLIENT ID ".json_encode($payment->client_id)."|QUICKBOOKS AUTHENTICATION FAILED.");
                    } */
                    Log::info("LogBatchPaymentFail |CLIENT ID ".json_encode($payment->client_id)."|QUICKBOOKS ERROR|".json_encode($error->getResponseBody())."CODE|".$error->getHttpStatusCode());
                    throw $error;
                }
                else{
                    foreach($this->batch->intuitBatchItemResponses as $batchItemResponse){
                        //payments in the response batch
                        $this->response_ids[] = $batchItemResponse->batchItemId;

                        $response['Id'] = $batchItemResponse->entity->Id;
                        $response['SyncToken'] = $batchItemResponse->entity->SyncToken;
                        $response['CreatedDate'] = $batchItemResponse->entity->MetaData->CreateTime;
                        $response['UnappliedAmt'] = $batchItemResponse->entity->UnappliedAmt;

                        DB::table('sync_payments')
                        ->where('payment_id', $batchItemResponse->batchItemId)
                        ->update([
                        'status' => 2,
                        'qb_id' => $batchItemResponse->entity->Id,
                        'response_message' => $error,
                        //'qb_id' => $response["Id"] ?? 0,
                        'line_items' =>json_encode( $payload->Line, true),
                        'response' => json_encode($response, true),
                    ]);

                    Log::info("LogPaymentInBatchPayment | payment created response |Request->".json_encode($payment->client_id)."|Response =>".json_encode($batchItemResponse));

                    }
                }

                Log::info($batchItemResponse->batchItemId);
            });
            //Log::info("LogPaymentInBatchPayment | payment created response |Request->".json_encode($this->data)."|Response =>".json_encode($batchItemResponse));
        }

        //Failed Payments
        $this->payload_ids = array_diff($this->payload_ids, $this->response_ids);
        print_r($this->payload_ids);
        if(!empty($this->payload_ids)){

        }

        //return true;
    }


    public function processPayment($data){
        $id = $data->customer_qb;
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
                    "value" =>$data->customer_qb,
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
