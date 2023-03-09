<?php
namespace App\Services;

use App\Helpers\Utils;
use App\Services\VendorServices;
use App\Models\BillItem;
use App\Models\DB_Bill;
use Illuminate\Http\Request;
use QuickBooksOnline\API\Facades\Bill;
use QuickBooksOnline\API\Facades\Item;
use QuickBooksOnline\API\Facades\Vendor;
use Illuminate\Support\Facades\Validator;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use Illuminate\Support\Facades\Log;



//session_start();
class BillServices {

    protected $dataService;

    protected $data;
    public function __construct($request){
        $this->data["user_id"] = Utils::getApiUser($request);
        $dataService = new DataServiceHelper($this->data);

        $dataService = new DataServiceHelper($this->data);
        $this->dataService = $dataService->getDataService();
    }

    public function index(){

        $allbills = $this->dataService->Query("SELECT * FROM Bill ");

        return $allbills;

        // return  $this->dataService->Query("SELECT * FROM Bill ");
    }

    public function store( $data)
{
    Log::info("LogBill | Bill request payload created ".json_encode($data));
    $validator = Validator::make($data->all(), [
        'vendor_name' => 'required',
        //'vendor_code' => 'required',
        'reference_number' => 'required',
        'due_date' => 'required',
        'line_items' => 'required|array',
        'line_items.*.amount' => 'required|integer',
    'line_items.*.item_name' => 'required|max:50',
    'line_items.*.quantity'    => 'required|integer',
    'line_items.*.unit_price'    => 'required|integer',
    //'line_items.*.item_code'    => 'required|max:20',

    ]);

    if($validator->fails()) {
        return [
            'status' => false,
            'errors' => $validator->messages(),
            'code' => 401
        ];
    }

    $vendor = $this->dataService->Query("SELECT * FROM Vendor where DisplayName = '".$data->vendor_name."'  ");
    if(!$vendor)
    {
        return [
            'status' => false,
            'errors' => "The vendor Name does not exist in quickbooks. create or confirm the correct details",
            'code' => 401
        ];
    }

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
    //Log::info($items_ids);

    if(sizeOf($line_items) <> sizeOf($data->line_items))
    {
        return [
            'status' => false,
            'errors' => "There are some missing items on the system. Existing ones are ".json_encode($line_items),
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
        $line_item["DetailType"] = "ItemBasedExpenseLineDetail";
        $line_item["ItemBasedExpenseLineDetail"]["ItemRef"]["value"] = $items_ids[$key];
        $line_item["ItemBasedExpenseLineDetail"]["UnitPrice"] = $item['unit_price'];
        $line_item["ItemBasedExpenseLineDetail"]["Qty"] = $item['quantity'];
        //$line_item["SalesItemLineDetail"]["BillableStatus"] = "NotBillable";
        $line_item["ItemBasedExpenseLineDetail"]["TaxCodeRef"] = "NON";


        $Line[] = $line_item;
    }
    $data["line"] = $Line;

    $bill = Bill::create([
        "DocNumber" => $data->reference_number,
        "DueDate" => $data->due_date,
        "Line" => $Line,
        "VendorRef" => [
            "value" => $vendor[0]->Id,
            "name" => $vendor[0]->DisplayName
        ],
    ]);


    $response = $this->dataService->Add($bill);
	$error = $this->dataService->getLastError();
	if ($error) {
        $data['status'] = 5;
        $this->storeBill($data,$response = null ,$error->getIntuitErrorDetail());

		Log::info("LogBill |Error|Request =>|Error Response".$error->getHttpStatusCode()."|
		".$error->getOAuthHelperError()."|".$error->getResponseBody());
        return ['status'=>false,'message'=>'We have received an Error'.$error->getIntuitErrorDetail(),'code'=>$error->getHttpStatusCode()];
    } else  if ($response) {
        $response_data['Id'] = $response->Id;
        $response_data['SyncToken'] = $response->Id;
        $response_data['CreatedDate'] = $response->MetaData->CreateTime;


        $data['Id'] = $response->Id;
        $data['status'] = 2; // success, happy path
        $this->storeBill($data,$response_data, $error = null);

        return ['status'=>true,"payment_id"=>$response->Id,"message"=>"Successfully created a Bill", "code" => 200];
    }
    else{
        ///No error and no response
        //could have failed or succeeded but no error or response
        //TODO before re-push check if payment  created
        $data['status'] = 3;
        $this->storeBill($data);
    }
    }


    public function storeBill($data,$response = null, $error = null){
        return DB_Bill::create([
            'vendor_name' => $data["vendor_name"],
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





    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'VendorName' => 'required',
    //         'VendorCode' => 'required',
    //         'RefrenceNo' => 'required',
    //         'DueDate' => 'required',
    //         'Amount' => 'required',
    //         'ItemName' => 'required',
    //         'ItemDescription' => 'required',
    //         'ItemCode' => 'required',
    //         'Quantity' => 'required',
    //         'UnitPrice' => 'required',
    //     ]);

    //     if($validator->fails()) {
    //         return response()->json([
    //             'status' => 422,
    //             'errors' => $validator->messages()
    //         ], 422);
    //     } else{
    //         $payment = Bill::create([
    //             "DocNumber" => "$request->VendorCode",
    //             "DueDate" => "$request->DueDate",
    //             "Line" => [
    //                 [
    //                     // "Id" => $request->id,
    //                     "Amount" => $request->Amount,
    //                     "Description" => "$request->ItemDescription",
    //                     "DetailType" => "ItemBasedExpenseLineDetail",
    //                     "ItemBasedExpenseLineDetail"=> [
    //                         "ItemRef"=> "$request->ItemCode",
    //                         "ClassRef"=> "",
    //                         "UnitPrice"=> "$request->UnitPrice",
    //                         "RatePercent"=> "",
    //                         "PriceLevelRef"=> "",
    //                         "MarkupInfo"=> "",
    //                         "Qty"=> "$request->Quantity",
    //                         "UOMRef"=> "",
    //                         "ItemAccountRef"=> "",
    //                         "InventorySiteRef"=> "",
    //                         "TaxCodeRef"=> "NON",
    //                         "TaxClassificationRef"=> "",
    //                         "CustomerRef"=> "",
    //                         "BillableStatus"=> "NotBillable",
    //                         "TaxInclusiveAmt"=> "",
    //                         "ItemBasedExpenseLineDetailEx"=> ""
    //                     ],
    //                 ],

    //             ],
    //             "VendorRef" => [
    //                 "value" =>"$request->RefrenceNo",
    //                 "name" => "$request->VendorName"
    //             ],
    //         ]);

    //         $allVendors = $this->dataService->Query("SELECT * FROM Vendor ");


    //         // return $allVendors;

    //         $found = false;

    //         for ($i = 0; $i < count($allVendors); $i++) {
    //             if ($allVendors[$i]->Id == $request->RefrenceNo) {
    //                     // If the ID is found, set the flag and break out of the loop
    //                     $found = true;
    //                 break;
    //             }
    //         }

    //         if ($found) {
    //             $result = $this->dataService->Add($payment);
    //             $result = json_encode($result, JSON_PRETTY_PRINT);
    //             print_r($result);
    //             // echo "ID $request->RefrenceNo was found in the array!";
    //         } else {
    //             echo "Vendor ID $request->RefrenceNo was not found in the vendor list .";
    //         }



    //         // if(!$request->RefrenceNo) {
    //         //     return response()->json([
    //         //         'status' => 500,
    //         //         'message' => 'vendor does not exist'
    //         //     ], 500);
    //         // };

    //         // $result = $this->dataService->Add($payment);
    //         // $result = json_encode($result, JSON_PRETTY_PRINT);
    //         // print_r($result);



    //         // if($payment) {
    //         //     return response()->json([
    //         //         'status' => 200,
    //         //         'message' => 'Bill added successfully'
    //         //     ], 200);
    //         // } else {
    //         //     return response()->json([
    //         //         'status' => 500,
    //         //         'message' => 'something went wrong'
    //         //     ], 500);
    //         // }

    //     }

    // }


    // public function vendors(Request $request){

    //     $allVendors = $this->dataService->Query("SELECT * FROM Vendor ");

    //     return $allVendors;

    //     $found = false;

    //     for ($i = 0; $i < count($allVendors); $i++) {
    //         if ($allVendors[$i]["id"] == $request->RefrenceNo) {
    //           // If the ID is found, set the flag and break out of the loop
    //           $found = true;
    //           break;
    //         }
    //     }

    //     if ($found) {
    //         echo "ID $request->RefrenceNo was found in the array!";
    //       } else {
    //         echo "ID $request->RefrenceNo was not found in the array.";
    //       }

    // }

}
