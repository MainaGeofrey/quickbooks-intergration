<?php
namespace App\Services;
use App\Models\DB_Invoice;
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
            'reference_number' => 'required|string',
            'due_date' => 'required|string',
            'account_number' => 'required|string',
            "line_items"    => "required|array|min:1",
            'line_items.*.amount' => 'required|numeric|gt:0',
            'line_items.*.item_name' => 'required|max:50',
            'line_items.*.quantity'    => 'required|integer',
            'line_items.*.unit_price'    => 'required|integer',
           // 'line_items.*.item_code'    => 'required|max:20',
            //"Line.*"  => "required|array|min:3",
            //'username' => 'required|unique:users,username,NULL,id,deleted_at,NULL',
            //'email' => 'nullable|email|unique:users,email,NULL,id,deleted_at,NULL',

        ]);

        if($validator->fails()){

            return ["status" => false,"errors" => $validator->errors()->getMessages()];
        }

        $name = $data["account_number"];
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ");
        if(!$customer){
            return ["message" => "Account number $name Not Found", "code" => 404];
        }
        $id = $customer[0]->Id;


        $line_items = [];
        $item_names = array_column($data->line_items,"item_name");

        $sql_products = "select * from Item where Name in ('".join("','",$item_names)."')";
        $items = $this->dataService->Query($sql_products);

        if(!$items)
        {
            return [
                'status' => false,
                'errors' => "the item do not exist in quickbooks. create or confirm the correct details",
                'code' => 401
            ];

        }
        $line_items = [];
        //$items_ids = [];
        foreach($items as $item)
        {
            $line_items[$item->Name]=$item->Name;
            $items_ids[] = $item->Id;
        }
        Log::info($items_ids);

        if(sizeOf($line_items) <> sizeOf($data->line_items))
        {
            return [
                'status' => false,
                'errors' => "There are some missing item on the system. Existing ones are ".json_encode($line_items),
                'code' => 401
            ];
        }/*
        1. You should first search and get the vendor by display name (vendor )
        2. You search for each item and see if the items returned have the same size as the line items select * from items where displayname = $code or displayname = code2
        3. Create the payload
        */

    $Line = [];
        foreach ($data->line_items as $key => $item) {

            $line_item["Description"] = $item["description"];
            $line_item["Amount"] = $item["amount"];
            $line_item["DetailType"] = "SalesItemLineDetail";
            $line_item["SalesItemLineDetail"]["ItemRef"]["value"] = $items_ids[$key];
            $line_item["SalesItemLineDetail"]["UnitPrice"] = $item['unit_price'];
            $line_item["SalesItemLineDetail"]["Qty"] = $item['quantity'];
            //$line_item["SalesItemLineDetail"]["BillableStatus"] = "NotBillable";
            $line_item["SalesItemLineDetail"]["TaxCodeRef"] = "NON";


            $Line[] = $line_item;
        }
        $data["line"] = $Line;


        try {
            $invoice = Invoice::create([
                "Line" => $Line,
                "DocNumber" => $data["reference_number"],
                //"DocNumber" => $data["reference_number"],
                //"DueDate" => $data["due_date"],
                //"DateCreated" =>$data["date_created"],
                "CustomerRef" => [
                    "value" => $id,
                    //"name" => $data->data["CustomerRef"]["DisplayName"]
                ]
            ]);

            Log::info("LogInvoice | invoice request payload created ".json_encode($data));

			$response = $this->dataService->Add($invoice);
			$error = $this->dataService->getLastError();
			if ($error) {
                $data['status'] = 5;
                $this->storeInvoice($data,$response = null ,$error->getIntuitErrorDetail());

				Log::info("LogInvoice |Error|Request =>".json_encode($invoice)."|Error Response".$error->getHttpStatusCode()."|
					".$error->getOAuthHelperError()."|".$error->getResponseBody());
				return ['status'=>false,'message'=>'We have received an Error'.$error->getIntuitErrorDetail(),'code'=>$error->getHttpStatusCode()];
            } else if ($response) {
                $response_data['Id'] = $response->Id;
                $response_data['SyncToken'] = $response->Id;
                $response_data['CreatedDate'] = $response->MetaData->CreateTime;

                $data['Id'] = $response->Id;
                $data['status'] = 2; // success, happy path
                $this->storeInvoice($data,$response_data, $error = null);

                return ['status'=>true,"invoice_id"=>$response->Id,"message"=>"Successfully created an invoice.", "code" => 200];
            }
            else{
                ///No error and no response
                //could have failed or succeeded but no error or response
                //TODO before re-push check if invoice  created
                $data['status'] = 3;
                $this->storeInvoice($data);
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

        return $result;
     }


    public function storeInvoice($data,$response = null, $error = null){
        return DB_Invoice::create([
            'account_name' => $data["account_number"],
            'reference_number' => $data["reference_number"],
            'due_date' => $data["due_date"],
            'date_created' => $data["date_created"],
            'client_id' => $this->data['user_id'],
            'status' => $data["status"] ?? 0,
            'response_message' => $error,
            'qb_id' => $response["Id"] ?? 0,
            'line_items' =>json_encode( $data["line"], true),
            'response' => json_encode($response, true),
        ]);
     }
}
