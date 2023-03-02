<?php
namespace App\Services;
use App\Services\DataServiceHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Facades\Payment;

//session_start();
class InvoiceServices {

    protected $dataService;
    protected $data;
    public function __construct($data){
        $this->data = $data;
        $dataService = new DataServiceHelper($this->data);

        $this->dataService = $dataService->getDataService();
    }

    public function index(){


        return  $this->dataService->Query("SELECT * FROM Invoice ");
    }
    public function store($data){
        $validator = Validator::make($data->all(), [
            'AccountNumber' => 'required|string',
            "Line"    => "required|array|min:1",
            //"Line.*"  => "required|array|min:3",
            //'username' => 'required|unique:users,username,NULL,id,deleted_at,NULL',
            //'email' => 'nullable|email|unique:users,email,NULL,id,deleted_at,NULL',

        ]);

        if($validator->fails()){

            return ["message" => $validator->errors()->getMessages(), "code" => 422];
        }


      /*  foreach($data->Line as $key => $value){
            $line_validator = Validator::make($value, [
                'Description' => 'required|string',
                'Amount' =>  'required|numeric|gt:0',
                'DetailType' => 'required|string',
                "SalesItemLineDetail"    => "required|array|min:1",
                //'username' => 'required|unique:users,username,NULL,id,deleted_at,NULL',
                //'email' => 'nullable|email|unique:users,email,NULL,id,deleted_at,NULL',

            ]);

            if($line_validator->errors()){

                return ["message" => $line_validator->errors(), "code" => 422];
            }

        } */

        Log::info("LogInvoice | invoice request  ".__METHOD__."|".json_encode($data).json_encode($this->data));
        $name = $data["AccountNumber"];
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ");
        if(!$customer){
            return ["message" => "Account by name $name Not Found", "code" => 404];
        }

        $id = $customer[0]->Id;

        try {
            $invoice = Invoice::create([
                "Line" => $data['Line'],
                    "CustomerRef" => [
                    "value" => $id,
                    //"name" => $data->data["CustomerRef"]["DisplayName"]
                    ]
            ]);

            $result = $this->dataService->Add($invoice);
            //$invoice = $this->invoiceResponse($result,$name);
            Log::info("LogInvoice | invoice created successfully  ".__METHOD__."|".json_encode($invoice)."|Invoice Created|".json_encode($this->data));
            // $result = json_encode($result, JSON_PRETTY_PRINT);
            // print_r($result);

            return ["invoice_id" => $result->Id,"status" =>true, "code" => 200];
        } catch (\Throwable $th) {
            //throw $th;

            return ["message" => $th->getMessage(),"status" =>false, "code" => 200];
        }
    }

    public function invoiceResponse($data, $name){
        $invoice = [];
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ");

        $invoice["invoice_id"] = $data->Id;
        $invoice["account_name"] = $name;
        $invoice["created_time"] = $data->MetaData->CreateTime;
        $payment["amount"] = $data->TotalAmt;


        return $invoice;
    }


    public function show($data){
        //Query Open Invoices
        $result = $this->dataService->Query("SELECT * FROM Invoice WHERE CustomerRef = '$data->id' ");
        //$result = json_encode($result, JSON_PRETTY_PRINT);
        //print_r($result);

        return $result;
     }
}
