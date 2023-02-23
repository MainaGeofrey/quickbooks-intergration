<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use QuickBooksOnline\API\Facades\Customer;



//session_start();
class CustomerServices {

    protected $dataService;
    public function __construct(){
        $dataService = new DataServiceHelper();

        $this->dataService = $dataService->getDataService();
    }

    public function index(){
        $result = $this->dataService->Query("SELECT * FROM Customer ");


        return $result;
    }
    public function store($data){
        $customer = Customer::create([
            "BillAddr" => [
                "Line1" => "1238 Main Street",
                "City" => "Mountain View",
                "Country" => "USA",
                "CountrySubDivisionCode" => "CA",
                "PostalCode" => "940042"
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
            "DisplayName" => $data->data['DisplayName'],
            "PrintOnCheckName" => $data->data['PrintOnCheckName'],
            //"UserId" => $data->data['UserId'],
            //"Active" => $data->data['Active'],
            "PrimaryPhone" => [
                "FreeFormNumber" => "(555) 555-500"
            ],
            //"AlternatePhone" => $data->data['AlternatePhone'],
            "PrimaryEmailAddr" => [
                "Address" => "jdrew@myemail.com"
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

        return $result;
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
