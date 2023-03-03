<?php
namespace App\Services;
//require_once(__DIR__ . '/../../vendor/autoload.php');
require __DIR__.'/../../vendor/autoload.php';
use Illuminate\Support\Facades\Log;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\DataService;
use App\Services\QBAuthService;

session_start();

class DataServiceHelper {
    function getDataService()
    {
        $config = config("quickbooks");

        //TODO refresh token only after 1 hr
        if(array_key_exists('expires_in', $_SESSION  ) &&strtotime($_SESSION["expires_in"]) < date('Y-m-d H:i:s') ) {
            $newAccessTokenObj = $this->refreshToken($config);
            // $config = include('/../../config/quickbooks.php');
             $dataService = DataService::Configure(array(
                 'auth_mode' => 'oauth2',
                 'ClientID' => $config['client_id'],
                 'ClientSecret' =>  $config['client_secret'],
                 'RedirectURI' => $config['oauth_redirect_uri'],
                 'scope' => $config['oauth_scope'],
                 'baseUrl' => "development",

                 'accessTokenKey' => $newAccessTokenObj->getAccessToken(),
                 'QBORealmID' => $config['QBORealmID'],
                 "expires_in"=>  $newAccessTokenObj->getAccessTokenExpiresAt()
             ));
             $_SESSION["access_token"] = $newAccessTokenObj->getAccessToken();
             $_SESSION["expires_in"] = $newAccessTokenObj->getAccessTokenExpiresAt();
        }
        else{
        // Create SDK instance

        $newAccessTokenObj = $this->refreshToken($config);
       // $config = include('/../../config/quickbooks.php');
        $dataService = DataService::Configure(array(
            'auth_mode' => 'oauth2',
            'ClientID' => $config['client_id'],
            'ClientSecret' =>  $config['client_secret'],
            'RedirectURI' => $config['oauth_redirect_uri'],
            'scope' => $config['oauth_scope'],
            'baseUrl' => "development",

            'accessTokenKey' => $newAccessTokenObj->getAccessToken(),
            'QBORealmID' => $config['QBORealmID'],
            "expires_in"=>  $newAccessTokenObj->getAccessTokenExpiresAt()
        ));
        $_SESSION["access_token"] = $newAccessTokenObj->getAccessToken();
        $_SESSION["expires_in"] = $newAccessTokenObj->getAccessTokenExpiresAt();
    }


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
    //    print_r($newAccessTokenObj->getAccessToken());
        //print_r($companyInfo);
        //return $dataService->updateOAuth2Token($accessToken);
        return $dataService;
        }

        public function refreshToken($config){
            $oauth2LoginHelper = new OAuth2LoginHelper($config['client_id'],$config['client_secret']);
            $newAccessTokenObj = $oauth2LoginHelper->refreshAccessTokenWithRefreshToken($config['refresh_token']);
            //$newAccessTokenObj->setRealmID($config['QBORealmID']);

            return $newAccessTokenObj;
        }
}
//$result = getDataService();

?>
