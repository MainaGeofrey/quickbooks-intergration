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
        $validator = Validator::make($data->data, [
            'AccountName' => 'required|string',
            //'username' => 'required|unique:users,username,NULL,id,deleted_at,NULL',
            //'email' => 'nullable|email|unique:users,email,NULL,id,deleted_at,NULL',

        ]);

        if($validator->fails()){

            return response()->json(["message" => "Please provide the AccountName", "code" => 422]);
        }
        $name = $data->data["AccountName"];
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$name' ");


        if($customer){

            //TODO Customer update
            Log::info("CUSTOMER EXISTS");
            //Log::info("LogCustomer | customer request updated successfully  ".__METHOD__."|".json_encode($customer)."|Customer Created|".json_encode($this->data));
            return response()->json(["message" => "Account by name $name Exists", "code" => 422]);
        }


        Log::info("LogCustomer | customer request  ".__METHOD__."|".json_encode($data->data).json_encode($this->data));
        $customer = Customer::create([
            "BillAddr" => [
                "Line1" => $data->data['BillAddr']['Line1'],
                "City" =>  $data->data['BillAddr']['City'],
                //"Country" => "USA",
                //"CountrySubDivisionCode" => "CA",
                "PostalCode" =>  $data->data['BillAddr']['PostalCode'],
            ],
            //"CustomField" => $data->data['CustomField'],
            //"Organization" => $data->data['Organization'],
            "Notes" => $data->data['Notes'],
            "Title" => $data->data['Title'],
            "GivenName" => $data->data['GivenName'],
            "MiddleName" => $data->data['MiddleName'],
            "FamilyName" => $data->data['FamilyName'],
            "Suffix" => $data->data['Suffix'],
            "Balance" => $data->data['Balance'],
            "FullyQualifiedName" => $data->data['FullyQualifiedName'],
            "CompanyName" => $data->data['CompanyName'],
            "DisplayName" => $data->data['AccountName'],
            "PrintOnCheckName" => $data->data['PrintOnCheckName'],
            //"UserId" => $data->data['UserId'],
            //"Active" => $data->data['Active'],
            "PrimaryPhone" => [
                "FreeFormNumber" =>  $data->data['PrimaryPhone']['FreeFormNumber'],
            ],
            //"AlternatePhone" => $data->data['AlternatePhone'],
            "PrimaryEmailAddr" => [
                "Address" => $data->data['PrimaryEmailAddr']['Address'],
            ],
            //"WebAddr" => $data->data['WebAddr'],
            //"OtherContactInfo" => $data->data['OtherContactInfo'],
            "DefaultTaxCodeRef" => $data->data['DefaultTaxCodeRef'],
            //"ShipAddr" => $data->data['ShipAddr'],
            //"OtherAddr" => $data->data['OtherAddr'],
           // "ContactName" => $data->data['ContactName'],
            //"AltContactName" => $data->data['AltContactName'],
           // "CreditLimit" => $data->data['CreditLimit'],
            //"SecondaryTaxIdentifier" => $data->data['SecondaryTaxIdentifier'],
            //"ClientCompanyId" => $data->data['ClientCompanyId'],
        ]);


        $result = $this->dataService->Add($customer);
        $customer = $this->customerResponse($result);
        Log::info("LogCustomer | customer request created successfully  ".__METHOD__."|".json_encode($customer)."|Customer Created|".json_encode($this->data));

        return $customer;
    }

    public function customerResponse($data){
        $customer = [];

        $customer["CustomerId"] = $data->Id;
        $customer["AccountName"] = $data->DisplayName;
        $customer["MetaData"] = $data->MetaData;
        //$payment["UnappliedAmount"] = $data->TotalAmt;
        $customer["CustomerBalance"] = $data->Balance;

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
