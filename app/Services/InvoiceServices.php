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
            'account_number' => 'required|string',
            "line"    => "required|array|min:1",
            //"Line.*"  => "required|array|min:3",
            //'username' => 'required|unique:users,username,NULL,id,deleted_at,NULL',
            //'email' => 'nullable|email|unique:users,email,NULL,id,deleted_at,NULL',

        ]);

        if($validator->fails()){

            return ["message" => $validator->errors()->getMessages(), "code" => 422];
        }


        $Line = [];
        $line_item = [];
        foreach($data->line as $key => $value){
            $line_item["Description"] = $value["description"];
            $line_item["Amount"] = $value["amount"];
            $line_item["DetailType"] = $value["detail_type"];
            $line_item["SalesItemLineDetail"]["ItemRef"] = $value["sales_item_linedetail_item_ref"];
            $Line[] = $line_item;
            Log::info($Line);

        }

        Log::info("LogInvoice | invoice request  ".__METHOD__."|".json_encode($data).json_encode($this->data));
        $name = $data["account_number"];
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ");
        if(!$customer){
            return ["message" => "Account number $name Not Found", "code" => 404];
        }

        $id = $customer[0]->Id;

        try {
            $invoice = Invoice::create([
                "Line" => $Line,
                    "CustomerRef" => [
                    "value" => $id,
                    //"name" => $data->data["CustomerRef"]["DisplayName"]
                    ]
            ]);

            Log::info("LogPayment | payment request payload created ".json_encode($data));

			$response = $this->dataService->Add($invoice);
			$error = $this->dataService->getLastError();
			if ($error) {
				Log::info("LogInvoice |Error|Request =>".json_encode($invoice)."|Error Response".$error->getHttpStatusCode()."|
					".$error->getOAuthHelperError()."|".$error->getResponseBody());
				return ['status'=>false,'message'=>'We have received an Error'.$error->getIntuitErrorDetail(),'code'=>$error->getHttpStatusCode()];
            } else {

                return ['status'=>true,"invoice_id"=>$response->Id,"message"=>"Successfully created an invoice.", "code" => 200];
            }

          //  return ["invoice_id" => $result->Id,"status" =>true, "code" => 200];
        } catch (\Throwable $th) {
            //throw $th;

            return ["message" => $th->getMessage(),"status" =>false, "code" => 200];
        }
    }




    public function show($data){
        //Query Open Invoices
        $result = $this->dataService->Query("SELECT * FROM Invoice WHERE CustomerRef = '$data->id' ");
        //$result = json_encode($result, JSON_PRETTY_PRINT);
        //print_r($result);

        return $result;
     }
}
