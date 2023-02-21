<?php
namespace App\Services;
use QuickBooksOnline\API\Facades\Customer;

use App\Services\DataServices;

//session_start();
class CustomerServices {

    protected $dataService;
    public function __construct(){
        $dataService = new DataServiceHelper();
        $this->dataService = $dataService->getDataService();
    }
    public function store(){
        $customer = Customer::create([
            "BillAddr" => [
                "Line1" => "1238 Main Street",
                "City" => "Mountain View",
                "Country" => "USA",
                "CountrySubDivisionCode" => "CA",
                "PostalCode" => "940042"
            ],
            "Notes" => "Here are other details.",
            "Title" => "Mr",
            "GivenName" => "Student",
            "MiddleName" => "Student",
            "FamilyName" => "Student",
            "Suffix" => "Jr",
            "Balance" => "500",
            "FullyQualifiedName" => "Student 000",
            "CompanyName" => "STUDENT 000",
            "DisplayName" => "Student000",
            "PrimaryPhone" => [
                "FreeFormNumber" => "(555) 555-500"
            ],
            "PrimaryEmailAddr" => [
                "Address" => "jdrew@myemail.com"
            ]
        ]);


        $result = $this->dataService->Add($customer);
        $result = json_encode($result, JSON_PRETTY_PRINT);
        print_r($result);
    }


    public function show(){
       $result = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = 'Student000'");
       $result = json_encode($result, JSON_PRETTY_PRINT);
       print_r($result);
    }

    public function getCompanyInfo()
    {

        $companyInfo = $this->dataService->getCompanyInfo();

        print_r($companyInfo);
        return $companyInfo;
    }


}


?>
