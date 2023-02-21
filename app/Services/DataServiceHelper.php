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
            'baseUrl' => "development",

            'accessTokenKey' => 'eyJlbmMiOiJBMTI4Q0JDLUhTMjU2IiwiYWxnIjoiZGlyIn0..WDOZByvslzipERkOeVLTTQ.4NxsxdN1rUVnEdLcM4b7RUz5t4XWt3RNfyplyypqltqL54lcdBS3fcVoOvJX9VWQUgPAzR-SLDqUi5aRsxlppvy1YcgFDjjNoxn80dLZKqApg5dBKItjHuMFhssWk8pV3dwN_PlJJO_e8jy-j9huqkZAz1EkFZa0gU000T2a2IB-vZCii38OI8aME7_mtQ8FmxYY2wroRxn-np4tIUBcNvdoOfsSx6gzn9osT4cBJncpnAwSOquSF4CxwM6b73FZnIlj7npYPvzywTloQRutSAl_raUuZ-a1qA48RWtHXXL99qU-RTUBT-sxh9Yu8c87dewPRwOhH5w6FuvK2Xd0H6ApDLKpokj0avQDeU9aA_rdV0iWoRQ9gZwAe-YG-9ke5HmLqs6AJK96d2-R96CznYFP2u1ga7qy1Yl7FhShf0GFz0CxaiM10ff1ees8213_mGT_eDtMRUZD-FtkHI8Y0oepCiqt2e8a_bI3vV2hLv5w_23txCpHs0fssVilJpw7n3l3AiDrNvh379UFTrrgDb5QsZ_P3ziMAWUe5pwQj0T_OFXYWSXFb3H-3rpMQqKiliUWE4tNEeAScSIp7OWssb3LbSatumgOE2YFsOFlQOURD1npIniUWM7kqVnMOjU_ehL4M9KdsGRRyUzBa0EWW_PIL2hPEBR2iardytrz4akvGuCdFvb4b-wZaUekcJ75UsGxz7PkeAJmXTCCjWfGGStJ8dWtDNB0-z_zavOqQhxFYOBcY_IJWuE9BBbDkHj59wvTAimq4cf62TQENmSlG2REd_p_GFgLTLBwmnRpM1XGtwYm1e4AJWY_29FMFJCasKg4-n9E7rJTfoPwi1NzLasLI78ZLECC4JR8emrkgXGXuS6H5TY0WeuFvSTYXTHt.klWujs48VRInK-igdyx_Pg',
            'refreshTokenKey' => "AB11685694311HWcwam1Qo0UC9tVbnLWSTLYtvTdbmeo6889IW",
            'QBORealmID' => "4620816365281740380",
        ));

        /*
            * Retrieve the accessToken value from session variable
        */
      /*  if (isset($_SESSION['sessionAccessToken'])) {
            $accessToken = $_SESSION['sessionAccessToken'];
        }
        else{
            $authObj = new QBAuthService();
            $authObj->setToken($dataService);
        } */
        /*
        * Update the OAuth2Token of the dataService object
        */
        //$dataService->updateOAuth2Token($accessToken);
        //$companyInfo = $dataService->getCompanyInfo();

        //$dataService->disableLog();
        $dataService->setLogLocation("app/logs");

        //print_r($companyInfo);
        //return $dataService->updateOAuth2Token($accessToken);
        return $dataService;
        }
}
//$result = getDataService();

?>
