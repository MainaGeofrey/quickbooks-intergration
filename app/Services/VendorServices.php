<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use QuickBooksOnline\API\Facades\Vendor;



//session_start();
class VendorServices {

    protected $dataService;
    protected $data;
    public function __construct($data){
        $this->data = $data;
        $dataService = new DataServiceHelper($this->data);

        $this->dataService = $dataService->getDataService();
    }

    public function index(){
        $result = $this->dataService->Query("SELECT * FROM Vendor ");


        return $result;
    }
    public function store($data){
        $validator = Validator::make($data->all(), [
            'account_number' => 'required|string',
            'phone_number' => 'required|string',
            //'username' => 'required|unique:users,username,NULL,id,deleted_at,NULL',
            //'email' => 'nullable|email|unique:users,email,NULL,id,deleted_at,NULL',

        ]);

        if($validator->fails()){

            return ["message" => "Please provide the AccountNumber", "code" => 422];
        }
        $name = $data["account_number"];
        $vendor= $this->dataService->Query("SELECT * FROM Vendor WHERE DisplayName = '$name' ");


        if($vendor){

            Log::info("VENDOR EXISTS");
            return ["status"=> false,"message" => "Account by number $name Exists", "code" => 422];
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
                "Notes" => $data['notes']?? null,
                "Title" => $data['title']?? null,
                "GivenName" => $data['given_name']?? null,
                "MiddleName" => $data['middle_name']?? null,
                "FamilyName" => $data['family_name']?? null,
                "Suffix" => $data['suffix']?? null,
                "Balance" => $data['balance']?? null,
                "FullyQualifiedName" => $data['fully_qualified_name']?? null,
                "CompanyName" => $data['company_name']?? null,
                "DisplayName" => $data['account_number'],
                "PrintOnCheckName" => $data['print_on_check_name']?? null,
                //"UserId" => $data->data['UserId'],
                //"Active" => $data->data['Active'],
                "PrimaryPhone" => [
                    "FreeFormNumber" =>  $data['phone_number'],
                ]?? null,
                //"AlternatePhone" => $data->data['AlternatePhone'],
                "PrimaryEmailAddr" => [
                    "Address" => $data['email_addr']?? null,
                ]?? null,
                //"WebAddr" => $data->data['WebAddr'],
                //"OtherContactInfo" => $data->data['OtherContactInfo'],
                "DefaultTaxCodeRef" => $data['default_tax_code_ref']?? null,
                //"ShipAddr" => $data->data['ShipAddr'],
                //"OtherAddr" => $data->data['OtherAddr'],
            // "ContactName" => $data->data['ContactName'],
                //"AltContactName" => $data->data['AltContactName'],
            // "CreditLimit" => $data->data['CreditLimit'],
                //"SecondaryTaxIdentifier" => $data->data['SecondaryTaxIdentifier'],
                //"ClientCompanyId" => $data->data['ClientCompanyId'],
            ]);
            Log::info("LogVendor | vendor request payload created ".json_encode($data));

			$response = $this->dataService->Add($vendor);
			$error = $this->dataService->getLastError();
			if ($error) {
				Log::info("LogVendor|Request =>".json_encode($data)."|Error Response".$error->getHttpStatusCode()."|
					".$error->getOAuthHelperError()."|".$error->getResponseBody());
				return ['status'=>false,'message'=>'We have received an Error'.$error->getIntuitErrorDetail(),'code'=>$error->getHttpStatusCode()];
            } else {

                return ['status'=>true,"Vendor_id"=>$response->Id,"message"=>"Successfully created a vendor.","code" => 200];
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

    public function getCompanyInfo()
    {

        $companyInfo = $this->dataService->getCompanyInfo();


        return $companyInfo;
    }


}


?>
