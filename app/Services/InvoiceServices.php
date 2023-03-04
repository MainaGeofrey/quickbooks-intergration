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
            return response()->json([
                'status' => false,
                'errors' => "the item name do not exist in quickbooks. create or confirm the correct details"
            ], 401);

        }
        $line_items = [];
        //$items_ids = [];
        foreach($items as $item)
        {
            $line_items[$item->Id]=$item->Id;
            $items_ids[] = $item->Id;
        }
        Log::info($items_ids);

        if(sizeOf($line_items) <> sizeOf($data->line_items))
        {
            return response()->json([
                'status' => false,
                'errors' => "There are some missing item names on the system. Existing ones are ".json_encode($line_items)
            ], 401);
        }/*
        1. You should first search and get the vendor by display name (vendor )
        2. You search for each item and see if the items returned have the same size as the line items select * from items where displayname = $code or displayname = code2
        3. Create the payload
        */

$Line = [];
        foreach ($data->line_items as $key => $item) {

          /*  $line_items[] = [
                "Amount" => $item['amount'],
                "Description" => $item['description'],
                "DetailType" => "SalesItemLineDetail",
                "SalesItemLineDetail"=> [
                    "ItemRef"=>[
                        "value"=>1,
                      //  "name": "Pump"
                    ],
                    //"ItemRef"=> $item['item_code'],
                    "ClassRef"=> "",
                    "UnitPrice"=> $item['unit_price'],
                    "RatePercent"=> "",
                    "PriceLevelRef"=> "",
                    "MarkupInfo"=> "",
                    "Qty"=> $item['quantity'],
                    "UOMRef"=> "",
                    "ItemAccountRef"=> "",
                    "InventorySiteRef"=> "",
                    "TaxCodeRef"=> "NON",
                    "TaxClassificationRef"=> "",
                    "CustomerRef"=> "",
                    "BillableStatus"=> "NotBillable",
                    "TaxInclusiveAmt"=> "",
                    "ItemBasedExpenseLineDetailEx"=> ""
                ],
            ]; */

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





      /*  $Line = [];
        $line_item = [];
        foreach($data->line_items as $key => $value){
            $validator = Validator::make($value, [
                'description' => 'required|string',
                'amount' =>  'required|numeric|gt:0',
              //  'detail_type' => 'required|string',
                //'sales_item_line_detail_item_ref' => 'required|string',
            ]);

            if($validator->fails()){
                Log::info($value);
                return ["message" => $validator->errors()->getMessages(), "code" => 422];
            }

            $line_item["Description"] = $value["description"];
            $line_item["Amount"] = $value["amount"];
            $line_item["DetailType"] = "SalesItemLineDetail";
            $line_item["SalesItemLineDetail"]["ItemRef"] = 1;
            $Line[] = $line_item;

        } */



        //Log::info("LogInvoice | invoice request  ".__METHOD__."|".json_encode($data).json_encode($this->data));


        try {
            $invoice = Invoice::create([
                "Line" => $Line,
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
