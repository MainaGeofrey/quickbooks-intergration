<?php
namespace App\Services;
use Illuminate\Http\Request;
use QuickBooksOnline\API\Facades\Vendor;
use Illuminate\Support\Facades\Validator;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;

class VendorServices {

    protected $dataService;
    public function __construct(){
        $dataService = new DataServiceHelper();
        $this->dataService = $dataService->getDataService();
    }

    public function index()
    {
        $allvendors = $this->dataService->Query("SELECT * FROM Vendor ");

        return $allvendors;
    }

    public function store()
    {
        $vendor = Vendor::create([
            "BillAddr" => [
                "Line1"=> "Safaricom fiber wi-fi",
                "Line2"=> "Safaricom",
                "Line3"=> "29834 Mustang Ave.",
                "City"=> "Millbrae",
                "Country"=> "U.S.A",
                "CountrySubDivisionCode"=> "CA",
                "PostalCode"=> "94030"
            ],
            "TaxIdentifier"=> "99-5688293",
            "AcctNum"=> "35372649",
            "Title"=> "Ms.",
            "GivenName"=> "DianneA",
            "FamilyName"=> "BradleyF",
            "Suffix"=> "Sr.",
            "CompanyName"=> "Safaricom fiber wi-fi",
            "DisplayName"=> "Safaricom fiber wi-fi",
            "PrintOnCheckName"=> "Safaricom fiber wi-fi",
            "PrimaryPhone"=> [
                "FreeFormNumber"=> "(650) 555-2342"
            ],
            "Mobile"=> [
                "FreeFormNumber"=> "(650) 555-2000"
            ],
            "PrimaryEmailAddr"=> [
                "Address"=> "Adbradley@myemail.com"
            ],
            "WebAddr"=> [
              "URI"=> "http://ADiannesAutoShop.com"
          ]
        ]);
        $vendor = $this->dataService->Add($vendor);
        $error = $this->dataService->getLastError();
        if ($error) {
            echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
            echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
            echo "The Response message is: " . $error->getResponseBody() . "\n";
        }
        else {
            echo "Created Id={$vendor->Id}. Reconstructed response body:\n\n";
            $xmlBody = XmlObjectSerializer::getPostXmlFromArbitraryEntity($vendor, $urlResource);
            echo $xmlBody . "\n";
        }
    }
}
?>