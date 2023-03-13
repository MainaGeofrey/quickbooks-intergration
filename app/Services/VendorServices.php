<?php
namespace App\Services;

use App\Helpers\Utils;
use App\Models\DB_Vendor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use QuickBooksOnline\API\Facades\Vendor;



//session_start();
class VendorServices {

    protected $dataService;
    protected $data;
    public function __construct($request){
        $this->data["user_id"] = Utils::getApiUser($request);
        $dataService = new DataServiceHelper($this->data);

        $this->dataService = $dataService->getDataService();
    }

    public function index(){
        $result = $this->dataService->Query("SELECT * FROM Vendor ");


        return $result;
    }
    public function store($data){
        $validator = Validator::make($data->all(), [
            'vendor_name' => 'required|string',
            'phone_number' => 'required|string',
            'title' => 'sometimes|string',
            "given_name"=> 'sometimes|string',
            "middle_name"=> 'sometimes|string',
            "family_name"=> 'sometimes|string',
            "suffix"=> 'sometimes|string',
            "company_name"=> 'sometimes|string',
            'account_number' => 'required|string',
            'email_addr' => 'required|email',
            //"address" => 'required|string',
            "notes"=> 'sometimes|string',
			"balance"=>'sometimes|numeric|min:0',
			//"currency_code"=>"required|string"
            'bill_addr' => 'sometimes|array',
            'bill_addr.*line1' => 'sometimes|string',
            'bill_addr.*city' => 'sometimes|string',
            'bill_addr.*postal_code' => 'sometimes|string',
            'default_tax_code_ref' => 'sometimes|string',
            'print_on_check_name' => 'sometimes|string',
            'fully_qualified_name' => 'sometimes|string',

            //'username' => 'required|unique:users,username,NULL,id,deleted_at,NULL',
            //'email' => 'nullable|email|unique:users,email,NULL,id,deleted_at,NULL',

        ]);

        if($validator->fails()){

            return ["message" => "Please provide the AccountNumber", "code" => 422];
        }
        $name = $data["vendor_name"];
        $vendor= $this->dataService->Query("SELECT * FROM Vendor WHERE DisplayName = '$name' ");


        if($vendor){

            Log::info("VENDOR EXISTS");
            return ["status"=> false,"message" => "Vendor  $name Exists", "code" => 422];
        }


        //Log::info("LogVendor | vendor request  ".__METHOD__."|".json_encode($data).json_encode($this->data));

        try{
            $vendor = Vendor::create([
                "BillAddr" => [
                    "Line1" => $data['bill_addr']['line1']?? null,
                    "City" =>  $data['bill_addr']['city']?? null,
                    //"Country" => "USA",
                    //"CountrySubDivisionCode" => "CA",
                    "PostalCode" =>  $data['bill_addr']['postal_code']?? null,
                ]?? null,
                //"CustomField" => $data->data['CustomField'],
                //"Organization" => $data->data['Organization'],
                "Title" => $data['title']?? null,
                "GivenName" => $data['given_name']?? null,
                "MiddleName" => $data['middle_name']?? null,
                "FamilyName" => $data['family_name']?? null,
                "Suffix" => $data['suffix']?? null,
                "Balance" => $data['balance']?? null,
                "FullyQualifiedName" => $data['fully_qualified_name']?? null,
                "CompanyName" => $data['company_name']?? null,
                "DisplayName" => $data['vendor_name'],
                "AcctNum" => $data['account_number'],
                "PrintOnCheckName" => $data['print_on_check_name']?? null,
                "Active" => true,
                "PrimaryPhone" => [
                    "FreeFormNumber" =>  $data['phone_number'],
                ]?? null,
                "PrimaryEmailAddr" => [
                    "Address" => $data['email_addr']?? null,
                ]?? null,
                //"WebAddr" => $data->data['WebAddr'],
                //"OtherContactInfo" => $data->data['OtherContactInfo'],
                "DefaultTaxCodeRef" => $data['default_tax_code_ref']?? null,
                "Notes" => $data['notes'] ?? null,
            ]);
            Log::info("LogVendor | vendor request payload created ".json_encode($data));

			$response = $this->dataService->Add($vendor);
			$error = $this->dataService->getLastError();
			if ($error) {
                $data['status'] = 5;
                $this->storeVendor($data,$response = null ,$error->getIntuitErrorDetail());

				Log::info("LogVendor|Request =>".json_encode($data)."|Error Response".$error->getHttpStatusCode()."|
					".$error->getOAuthHelperError()."|".$error->getResponseBody());
				return ['status'=>false,'message'=>'We have received an Error'.$error->getIntuitErrorDetail(),'code'=>$error->getHttpStatusCode()];
            } else if ($response) {
                $response_data['Id'] = $response->Id;
                $response_data['SyncToken'] = $response->Id;
                $response_data['CreatedDate'] = $response->MetaData->CreateTime;


                $data['Id'] = $response->Id;
                $data['status'] = 2; // success, happy path
                $this->storeVendor($data,$response_data, $error = null);

                return ['status'=>true,"Vendor_id"=>$response,"message"=>"Successfully created a vendor.","code" => 200];
            }
            else{
                ///No error and no response
                //could have failed or succeeded but no error or response
                //TODO before re-push check if payment  created
                $data['status'] = 3;
                $this->storeVendor($data);
            }
        } catch (\Throwable $th) {
        //throw $th;


            return ["status" =>false,"message" => $th->getMessage(), "code" => 200];
        }

    }


    public function show($data){
        $result = $this->dataService->Query("SELECT * FROM Vendor WHERE DisplayName = '$data->DisplayName' ");


        return $result;
     }

     public function storeVendor($data,$response = null, $error = null){
        $customer_details = [
            "title" =>$data["title"] ?? null,
            "suffix" => $data["suffix"] ?? null,
            "given_name" => $data["given_name"] ?? null,
            "middle_name" => $data["middle_name"] ?? null,
            "family_name" => $data["family_name"] ?? null,
            "fully_qualified_name" => $data["fully_qualified_name"] ?? null,
            "print_on_check_name" => $data["print_on_check_name"]??null,
            "default_tax_code_ref" => $data["default_tax_code_ref"]?? null,
        ];
        return DB_Vendor::create([
            'vendor_name' => $data["vendor_name"],
            'account_number' => $data["account_number"],
            'company_name' => $data["company_name"],
            'reference_number' => $data["reference_number"],
            'email' => $data["email_addr"],
            'balance' => $data["balance"],
            'mobile_number' => $data["phone_number"],
            'client_id' => $this->data['user_id'],
            'notes' => $data['notes'],
            'customer_details' => json_encode( $customer_details, true),
            'status' => $data["status"] ?? 0,
            'response_message' => $error,
            'qb_id' => $response["Id"] ?? 0,
            'response' => json_encode($response, true),
            'bill_addr' =>json_encode( $data["bill_addr"], true),
        ]);
     }


}


?>
