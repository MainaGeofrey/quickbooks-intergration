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

    protected  $batch;
    public function __construct($request){
        $this->data["user_id"] = 12;// Utils::getApiUser($request);
        $dataService = new DataServiceHelper($this->data);

        $this->dataService = $dataService->getDataService();

        $this->batch = $this->dataService->CreateNewBatch();
    }


    public function index(){


        return  $this->dataService->Query("SELECT * FROM Payment ");
    }

    public function storeBatch(){

       $payments = DB::table('sync_payments')->where('status', 5)
       ->where('qb_id', 0)
       ->orderBy('payment_id', 'desc')
       ->select('account_name','reference_number','date_time','amount','mobile_number','line_items','notes','payment_id')
       ->chunk(2, function ( $payments) {
        print_r(count($payments));
           foreach ($payments as $payment) {
                $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$payment->account_name' ");
                if(!$customer){
                    print_r("null");
                // return ["message" => "Account number $name Not Found", "code" => 404];
                }
                print_r("true");
                $payload = [
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
                $payload = Payment::create($payload);

                $this->batch->AddEntity($payload, "CreatePayment", "Create");
                print_r("true");
           }
           print_r("trueB");

           $this->batch->ExecuteWithRequestID($this->generateRandomString());

           $error = $this->batch->getLastError();
           if ($error) {
         /*  echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
           echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
           echo "The Response message is: " . $error->getResponseBody() . "\n"; */
           return ["message" => $error->getResponseBody(), "status" => $error->getHttpStatusCode()];
           //exit();
           }
           else{
                DB::table('sync_payments')
                   ->where('payment_id', $payment->payment_id)
                   ->update([
                    'status' => 2,
                    'qb_id' => 01
                ]);

                //break;
           }
           $batchItemResponse_createPayment= $this->batch->intuitBatchItemResponses["CreatePayment"];
           if($batchItemResponse_createPayment->isSuccess()){
           $createdInvoice = $batchItemResponse_createPayment->getResult();
           echo "Create Payment success!:\n";
           //$xmlBody = XmlObjectSerializer::getPostXmlFromArbitraryEntity($createdInvoice, $something);
           //echo $xmlBody . "\n";

           print_r($createdInvoice);
           }
       });


       /* $error = $this->dataService->getLastError();
        if ($error) {
            $data['status'] = 5;

            Log::info("LogInvoice |Error|Request =>".json_encode($invoice)."|Error Response".$error->getHttpStatusCode()."|
                ".$error->getOAuthHelperError()."|".$error->getResponseBody());
            return ['status'=>false,'message'=>'We have received an Error'.$error->getIntuitErrorDetail(),'code'=>$error->getHttpStatusCode()];
        } else if ($response) {
            $response_data['Id'] = $response->Id;
            $response_data['SyncToken'] = $response->Id;
            $response_data['CreatedDate'] = $response->MetaData->CreateTime;

            $data['Id'] = $response->Id;
            $data['status'] = 2; // success, happy path

            return ['status'=>true,"invoice_id"=>$response->Id,"message"=>"Successfully created an invoice.", "code" => 200];
        }
        else{
            //TODO before re-push check if invoice  created
            $data['status'] = 3;
        } */
    }

    private function processPayment($data){
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$data->account_name' ");
        if(!$customer){
            print_r("null");
           // return ["message" => "Account number $name Not Found", "code" => 404];
        }
        $payload = [
            "CustomerRef"=>
            [
                "value" =>$customer[0]->Id,
                "name" =>$data->account_name,
            ],
            "Line" => $data->line_items,
            "TotalAmt" => $data->amount,
            "PaymentRefNum" => $data->reference_number,
            "TxnDate" => $data->date_time,
            "PrivateNote" => $data->notes,
            "CustomField" => $data->mobile_number,
        ];
        $payment = Payment::create($payload);

        return $payment;
    }
    public function storeBatchTets(){

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
