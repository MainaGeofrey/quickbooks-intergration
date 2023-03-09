<?php
namespace App\Services;

use App\Models\DB_Customer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use QuickBooksOnline\API\Facades\Customer;



//session_start();
class CustomerServices {

    protected $dataService;
    protected $data;
    public function __construct($data){
        $this->data = $data;
        $dataService = new DataServiceHelper($this->data);

        $this->dataService = $dataService->getDataService();
    }

    public function index(){
        $result = $this->dataService->Query("SELECT * FROM Customer ");


        return $result;
    }
    public function store($data){
        $validator = Validator::make($data->all(), [
            'title' => 'sometimes|string',
            "given_name"=> 'sometimes|string',
            "middle_name"=> 'sometimes|string',
            "family_name"=> 'sometimes|string',
            "suffix"=> 'sometimes|string',
            "company_name"=> 'sometimes|string',
            'account_number' => 'required|string',
            'phone_number' => 'required|string',
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
	return ["status"=>false,"message" => $validator->errors()->getMessages(), "code" => 422];
        }
        $name = $data["account_number"];
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ");


        if($customer){

            Log::info("CUSTOMER EXISTS");
            return ["status"=> false,"message" => "Account  $name Exists", "code" => 422];
        }


        //Log::info("LogCustomer | customer request  ".__METHOD__."|".json_encode($data).json_encode($this->data));

        try{
            $customer = Customer::create([
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
                "Active" => true,
               /* "CurrencyRef" => [
                        //"value" => $data['currency_code']?? null,
        //..             "name" => "Philippine Peso"
                    ]?? null, */
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
            Log::info("LogCustomer | customer request payload created ".json_encode($data));

			$response = $this->dataService->Add($customer);
			$error = $this->dataService->getLastError();
			if ($error) {
                $data['status'] = 5;
                $this->storeCustomer($data,$response = null ,$error->getIntuitErrorDetail());

				Log::info("LogCustomer|Request =>".json_encode($data)."|Error Response".$error->getHttpStatusCode()."|
					".$error->getOAuthHelperError()."|".$error->getResponseBody());
				return ['status'=>false,'message'=>'We have received an Error'.$error->getIntuitErrorDetail(),'code'=>$error->getHttpStatusCode()];
            } else if ($response) {
                $response_data['Id'] = $response->Id;
                $response_data['SyncToken'] = $response->Id;
                $response_data['CreatedDate'] = $response->MetaData->CreateTime;


                $data['Id'] = $response->Id;
                $data['status'] = 2; // success, happy path
                $this->storeCustomer($data,$response_data, $error = null);

                return ['status'=>true,"customer_id"=>$response,"message"=>"Successfully created a customer.","code" => 200];
            }
            else{
                ///No error and no response
                //could have failed or succeeded but no error or response
                //TODO before re-push check if payment  created
                $data['status'] = 3;
                $this->storeCustomer($data);
            }
        } catch (\Throwable $th) {
        //throw $th;


            return ["status" =>false,"message" => $th->getMessage(), "code" => 200];
        }

    }


    public function show($data){
        $result = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$data->DisplayName' ");


        return $result;
     }

     public function storeCustomer($data,$response = null, $error = null){
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
        return DB_Customer::create([
            'account_name' => $data["account_number"],
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
