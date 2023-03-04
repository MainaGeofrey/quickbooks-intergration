<?php
namespace App\Services;

use App\Services\VendorServices;
use App\Models\BillItem;
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
    public function __construct($data){

        $dataService = new DataServiceHelper($data);
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
        'vendor_code' => 'required',
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
            'errors' => "the item codes do not exist in quickbooks. create or confirm the correct details",
            'code' => 401
        ];

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
        return [
            'status' => false,
            'errors' => "There are some missing item codes on the system. Existing ones are ".json_encode($line_items),
            'code' => 401
        ];
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
    $line_item["DetailType"] = "ItemBasedExpenseLineDetail";
    $line_item["ItemBasedExpenseLineDetail"]["ItemRef"]["value"] = $items_ids[$key];
    $line_item["ItemBasedExpenseLineDetail"]["UnitPrice"] = $item['unit_price'];
    $line_item["ItemBasedExpenseLineDetail"]["Qty"] = $item['quantity'];
    //$line_item["SalesItemLineDetail"]["BillableStatus"] = "NotBillable";
    $line_item["ItemBasedExpenseLineDetail"]["TaxCodeRef"] = "NON";


    $Line[] = $line_item;
}

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
    print_r($response);
			$error = $this->dataService->getLastError();
			if ($error) {
				Log::info("LogBill |Error|Request =>|Error Response".$error->getHttpStatusCode()."|
					".$error->getOAuthHelperError()."|".$error->getResponseBody());
                return ['status'=>false,'message'=>'We have received an Error'.$error->getIntuitErrorDetail(),'code'=>$error->getHttpStatusCode()];
} else {
    # code...
    // Echo some formatted output
    return ['status'=>true,"payment_id"=>$response->Id,"message"=>"Successfully created a Bill"];
}

    // $name = $request["VendorName"];

    // $Vendor = $this->dataService->Query("SELECT * FROM Vendor WHERE DispalyName = '$name ");

    // if(!$Vendor){
    //     return ["status"=>false,"message" => "Vendor by name $name Not Found", "code" => 404];
    // }



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
