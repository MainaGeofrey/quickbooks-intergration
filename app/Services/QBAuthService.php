<?php
namespace App\Services;

use App\Services\DataServices;
use Illuminate\Support\Facades\Log;

//session_start();
class QBAuthService {


    public function setToken($dataService){
        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
        $authUrl = $OAuth2LoginHelper->getAuthorizationCodeURL();

        // Store the url in PHP Session Object;
        $_SESSION['authUrl'] = $authUrl;

        //set the access token using the auth object
        $parseUrl = $this->parseAuthRedirectUrl($_SERVER['QUERY_STRING']);

        /*
         * Update the OAuth2Token
         */
        $accessToken = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken($parseUrl['code'], $parseUrl['realmId']);
        $dataService->updateOAuth2Token($accessToken);

        /*
         * Setting the accessToken for session variable
         */
        $_SESSION['sessionAccessToken'] = $accessToken;
        if (isset($_SESSION['sessionAccessToken'])) {

            $accessToken = $_SESSION['sessionAccessToken'];
            $accessTokenJson = array('token_type' => 'bearer',
                'access_token' => $accessToken->getAccessToken(),
                'refresh_token' => $accessToken->getRefreshToken(),
                'x_refresh_token_expires_in' => $accessToken->getRefreshTokenExpiresAt(),
                'expires_in' => $accessToken->getAccessTokenExpiresAt()
            );
            $dataService->updateOAuth2Token($accessToken);
            //$oauthLoginHelper = $dataService -> getOAuth2LoginHelper();
           // $CompanyInfo = $dataService->getCompanyInfo();
        }


        return $accessTokenJson;

    }


    public function parseAuthRedirectUrl($url)
    {
        parse_str($url,$qsArray);
        return array(
            'code' => $qsArray['code'],
            'realmId' => $qsArray['realmId']
        );
    }
}

?>
