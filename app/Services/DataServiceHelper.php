<?php
namespace App\Services;
//require_once(__DIR__ . '/../../vendor/autoload.php');
require __DIR__.'/../../vendor/autoload.php';
use Illuminate\Support\Facades\Log;
use QuickBooksOnline\API\DataService\DataService;
use App\Services\QBAuthService;

session_start();

class DataServiceHelper {
    function getDataService()
    {

        // Create SDK instance
        $config = config("quickbooks");
       // $config = include('/../../config/quickbooks.php');
        $dataService = DataService::Configure(array(
            'auth_mode' => 'oauth2',
            'ClientID' => $config['client_id'],
            'ClientSecret' =>  $config['client_secret'],
            'RedirectURI' => $config['oauth_redirect_uri'],
            'scope' => $config['oauth_scope'],
            'baseUrl' => "development"
        ));

        if (isset($_SESSION['sessionAccessToken'])) {
            /*
            * Retrieve the accessToken value from session variable
            */
            $accessToken = $_SESSION['sessionAccessToken'];
        }
        else{
            $authObj = new QBAuthService();
            $authObj->setToken($dataService);
        }
        /*
        * Update the OAuth2Token of the dataService object
        */
        //$dataService->updateOAuth2Token($accessToken);
        //$companyInfo = $dataService->getCompanyInfo();

        //$dataService->disableLog();
        $dataService->setLogLocation("app/logs");

        //print_r($companyInfo);
        return $dataService->updateOAuth2Token($accessToken);
        }
}
//$result = getDataService();

?>
