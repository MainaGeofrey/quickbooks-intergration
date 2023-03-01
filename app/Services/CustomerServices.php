<?php
namespace App\Services;
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
            'AccountName' => 'required|string',
            //'username' => 'required|unique:users,username,NULL,id,deleted_at,NULL',
            //'email' => 'nullable|email|unique:users,email,NULL,id,deleted_at,NULL',

        ]);

        if($validator->fails()){

            return ["message" => "Please provide the AccountName", "code" => 422];
        }
        $name = $data["AccountName"];
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ");


        if($customer){

            //TODO Customer update
            Log::info("CUSTOMER EXISTS");
            //Log::info("LogCustomer | customer request updated successfully  ".__METHOD__."|".json_encode($customer)."|Customer Created|".json_encode($this->data));
            return ["message" => "Account by number $name Exists", "code" => 422];
        }


        Log::info("LogCustomer | customer request  ".__METHOD__."|".json_encode($data).json_encode($this->data));

        try{
            $customer = Customer::create([
                "BillAddr" => [
                    "Line1" => $data['BillAddr']['Line1']?? null,
                    "City" =>  $data['BillAddr']['City']?? null,
                    //"Country" => "USA",
                    //"CountrySubDivisionCode" => "CA",
                    "PostalCode" =>  $data['BillAddr']['PostalCode']?? null,
                ]?? null,
                //"CustomField" => $data->data['CustomField'],
                //"Organization" => $data->data['Organization'],
                "Notes" => $data['Notes']?? null,
                "Title" => $data['Title']?? null,
                "GivenName" => $data['GivenName']?? null,
                "MiddleName" => $data['MiddleName']?? null,
                "FamilyName" => $data['FamilyName']?? null,
                "Suffix" => $data['Suffix']?? null,
                "Balance" => $data['Balance']?? null,
                "FullyQualifiedName" => $data['FullyQualifiedName']?? null,
                "CompanyName" => $data['CompanyName']?? null,
                "DisplayName" => $data['AccountName'],
                "PrintOnCheckName" => $data['PrintOnCheckName']?? null,
                //"UserId" => $data->data['UserId'],
                //"Active" => $data->data['Active'],
                "PrimaryPhone" => [
                    "FreeFormNumber" =>  $data['PhoneNumber']?? null,
                ]?? null,
                //"AlternatePhone" => $data->data['AlternatePhone'],
                "PrimaryEmailAddr" => [
                    "Address" => $data['EmailAddr']?? null,
                ]?? null,
                //"WebAddr" => $data->data['WebAddr'],
                //"OtherContactInfo" => $data->data['OtherContactInfo'],
                "DefaultTaxCodeRef" => $data['DefaultTaxCodeRef']?? null,
                //"ShipAddr" => $data->data['ShipAddr'],
                //"OtherAddr" => $data->data['OtherAddr'],
            // "ContactName" => $data->data['ContactName'],
                //"AltContactName" => $data->data['AltContactName'],
            // "CreditLimit" => $data->data['CreditLimit'],
                //"SecondaryTaxIdentifier" => $data->data['SecondaryTaxIdentifier'],
                //"ClientCompanyId" => $data->data['ClientCompanyId'],
            ]);


            $result = $this->dataService->Add($customer);


            //$customer = $this->customerResponse($result);
            Log::info("LogCustomer | customer request created successfully  ".__METHOD__."|".json_encode($customer)."|Customer Created|".json_encode($this->data));


            return ["customer_id" => $result->Id,"status" =>true, "code" => 200];
        } catch (\Throwable $th) {
        //throw $th;


            return ["message" => $th->getMessage(),"status" =>false, "code" => 200];
        }

    }

    public function customerResponse($data){
        $customer = [];

        $customer["customer_id"] = $data->Id;
        $customer["account_number"] = $data->DisplayName;
        $customer["phone_number"] = $data->PrimaryPhone->FreeFormNumber;
        $customer["email_address"] = $data->PrimaryEmailAddr->Address;
        $customer["company_name"] = $data->CompanyName;
        $customer["FullyQualifiedName"] = $data->FullyQualifiedName;
        $customer["PrintOnCheckName"] = $data->PrintOnCheckName;
        $customer["customer_balance"] = $data->Balance;
        $customer["billing_address"] = $data->BillAddr->Line1;

        return $customer;
    }
    public function show($data){
        $result = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$data->DisplayName' ");


        return $result;
     }

    public function getCompanyInfo()
    {

        $companyInfo = $this->dataService->getCompanyInfo();


        return $companyInfo;
    }


}


?>
