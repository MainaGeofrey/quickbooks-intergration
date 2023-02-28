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
        $validator = Validator::make($data->data, [
            'AccountName' => 'required|string',
            //'username' => 'required|unique:users,username,NULL,id,deleted_at,NULL',
            //'email' => 'nullable|email|unique:users,email,NULL,id,deleted_at,NULL',

        ]);

        if($validator->fails()){

            return response()->json(["message" => "Please provide the AccountName", "code" => 422]);
        }


        Log::info("LogInvoice | invoice request  ".__METHOD__."|".json_encode($data->data).json_encode($this->data));
        $name = $data->data["AccountName"];
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ");
        if(!$customer){
            return response()->json(["message" => "Account by name $name Not Found", "code" => 404]);
        }

        $id = $customer[0]->Id;

        $invoice = Invoice::create([
            "Line" => $data->data['Line'],
                "CustomerRef" => [
                  "value" => $id,
                  //"name" => $data->data["CustomerRef"]["DisplayName"]
                ]
        ]);

        $result = $this->dataService->Add($invoice);
        $invoice = $this->invoiceResponse($result,$name);
        Log::info("LogInvoice | invoice request created successfully  ".__METHOD__."|".json_encode($invoice)."|Invoice Created|".json_encode($this->data));
       // $result = json_encode($result, JSON_PRETTY_PRINT);
       // print_r($result);

        return $invoice;
    }

    public function invoiceResponse($data, $name){
        $invoice = [];
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ");

        $invoice["InvoiceId"] = $data->Id;
        $invoice["AccountName"] = $name;
        $invoice["MetaData"] = $data->MetaData;
        //$payment["UnappliedAmount"] = $data->TotalAmt;
        $invoice["CustomerBalance"] = $customer[0]->Balance;

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
