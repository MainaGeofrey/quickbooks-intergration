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
			'title' => 'required|string',
			"given_name"=> 'required|string',
			"middle_name"=> 'required|string',
			"family_name"=> 'required|string',
			"suffix"=> 'required|string',
			    "company_name"=> 'required|string',
            'account_number' => 'required|string',
            'phone_number' => 'required|string',
            'email_addr' => 'required|email',
			"address" => 'required|string',
			"notes"=> 'required|string',
			"balance"=>'required|numeric|min:0',
			"currency_code"=>"required|string"
        ]);    
	    if($validator->fails()){
           return ["status"=>false,"message" => $validator->errors()->getMessages(), "code" => 422]; 
        }
        $name = $data["account_number"];
        $vendor= $this->dataService->Query("SELECT * FROM Vendor WHERE DisplayName = '$name' ");
        if($vendor){
            Log::info("VENDOR EXISTS");
            return ["status"=> false,"message" => "Account by number $name Exists", "code" => 422];
        }
        try{
            $vendor = Vendor::create([
                "BillAddr" => [
                    "Line1" => $data['address']?? null,
                    "City" =>   null,
                    //"Country" => "USA",
                    //"CountrySubDivisionCode" => "CA",
//                    "PostalCode" =>  $data['bill_addr']['postal_code']?? null,
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
		        "CurrencyRef" => [
                "value" => $data['currency_code']
//..                "name" => "Philippine Peso"
            ],
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
