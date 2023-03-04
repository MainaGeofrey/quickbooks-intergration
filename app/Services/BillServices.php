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

    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'vendor_name' => 'required',
        'vendor_code' => 'required',
        'reference_number' => 'required',
        'due_date' => 'required',
        'line_items' => 'required|array',
        'line_items.*.amount' => 'required|integer',
        'line_items.*.item_name' => 'required|max:50',
        'line_items.*.quantity'    => 'required|integer',
        'line_items.*.unit_price'    => 'required|integer',
        'line_items.*.item_code'    => 'required|max:20',

    ]);

    if($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->messages()
        ], 422);
    }

    $vendor = $this->dataService->Query("SELECT * FROM Vendor where DisplayName = '".$request->vendor_name."'  ");
    // print_r($vendor);
    // die();
    if(!$vendor)
{
    return response()->json([
        'status' => false,
        'errors' => "the vendor code does not exist in quickbooks. create or confirm the correct details"
    ], 401);
}
    
    $line_items = [];
    $item_codes = array_column($request->line_items,"item_code");
    // $sql_products = $this->dataService->Query("SELECT * FROM Items where DisplayName = '".$request->item_name."' ");
    $sql_products = "select * from items where displayName in ('".join("','",$item_codes)."')";
    $items = $this->dataService->Query($sql_products);
    if(!$items)
    {
        return response()->json([
            'status' => false,
            'errors' => "the item codes do not exist in quickbooks. create or confirm the correct details"
        ], 401);

    }
    $line_items = [];
foreach($items as $item)
{
$line_items[$item->displayName]=$item->Id;
}

if(sizeOf($line_items) <> sizeOf($request->line_items))
{
    return response()->json([
        'status' => false,
        'errors' => "There are some missing item codes on the system. Existing ones are ".json_encode($line_items)
    ], 401);
}/*
1. You should first search and get the vendor by display name (vendor )
2. You search for each item and see if the items returned have the same size as the line items select * from items where displayname = $code or displayname = code2
3. Create the payload
*/

    foreach ($request->line_items as $item) {
        $line_items[] = [
            "Amount" => $item['amount'],
            "Description" => $item['item_description'],
            "DetailType" => "ItemBasedExpenseLineDetail",
            "ItemBasedExpenseLineDetail"=> [
                "ItemRef"=>[
                    "value"=>$line_items[$item['item_code']],
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
        ];
    }

    $bill = Bill::create([
        "DocNumber" => $request->reference_number,
        "DueDate" => $request->due_date,
        "Line" => $line_items,
        "VendorRef" => [
            "value" => $vendor[0]->Id,
            "name" => $vendor[0]->DisplayName
        ],
    ]);


    $response = $this->dataService->Add($bill);
			$error = $this->dataService->getLastError();
			if ($error) {
				Log::info("LogPayment |Error|Request =>|Error Response".$error->getHttpStatusCode()."|
					".$error->getOAuthHelperError()."|".$error->getResponseBody());
				return ['status'=>false,'message'=>'We have received an Error'.$error->getResponseBody()];
} else {
    # code...
    // Echo some formatted output
    return ['status'=>true,"payment_id"=>$response->Id,"message"=>"Successfully created a payment"];
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
