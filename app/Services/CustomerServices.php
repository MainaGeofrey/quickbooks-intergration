<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
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
            "DisplayName" => $data->data['DisplayName'],
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
