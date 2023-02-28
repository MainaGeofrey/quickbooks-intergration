<?php
namespace App\Services;
//require_once(__DIR__ . '/../../vendor/autoload.php');
require __DIR__.'/../../vendor/autoload.php';

use App\Helpers\Utils;

use App\Models\QBConfig;
use DateTime;
use Illuminate\Support\Facades\Log;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\DataService;
use App\Services\QBAuthService;

session_start();

class DataServiceHelper {

    protected $data;

    public function __construct($data){
        $this->data = $data;
    }
    function getDataService()
    {
        $config = config("quickbooks");

        $qb_token = QBConfig::where("user_id", $this->data["user_id"])->first();
        //Log::info($qb_token->refresh_token);
        if($qb_token){
            //
           // Log::info("QB_TOKEN");

            $date1 = new DateTime(date('Y-m-d H:i:s',strtotime($qb_token->expires_in)));
            $date2 = new DateTime(date('Y-m-d H:i:s'));

            if( $date1 < $date2 ) {
                //token is expired
                Log::info("QB_TOKEN_EXPIRED");
                $config["refresh_token"] = $qb_token->refresh_token;
                $newAccessTokenObj = $this->refreshToken($config);


                    try{
                        $access_token = $newAccessTokenObj->getAccessToken();
                        $refresh_token = $newAccessTokenObj->getRefreshToken();
                        $expires_in = $newAccessTokenObj->getAccessTokenExpiresAt();
                        Log::info("QB_ACCESS_TOKEN_UPDATED");

                        try{
                            $qb_token->Update([
                                "access_token" => $access_token,
                                "refresh_token" => $refresh_token,
                                "expires_in" => $expires_in,

                            ]);


                        }
                        catch(\Exception $exception){
                            Log::info("QB_ACCESS_DATABASE_UPDATE".$exception->getMessage());
                            Log::info($exception);
                        }

                    }
                    catch(\Exception $exception){
                        //Log::info($exception->getMessage());
                        Log::info("QB_ACCESS_TOKEN_REFRESH_FAIL".$exception->getMessage());
                        return response()->json(["message" => "Refresh OAuth 2 Access token with Refresh Token failed", "code" => 400]);
                    }


            }
            else{
                //stored access token
                Log::info('QB_ACCESS_TOKEN_VALID');
                $access_token = $qb_token->access_token;
                $refresh_token = $qb_token->refresh_token;
                $expires_in = $qb_token->expires_in;

            }
        }
        else{
            //TODO get config from DB for new dataservice
            try{
                $newAccessTokenObj = $this->refreshToken($config);
                $access_token = $newAccessTokenObj->getAccessToken();
                $refresh_token = $newAccessTokenObj->getRefreshToken();
                $expires_in = $newAccessTokenObj->getAccessTokenExpiresAt();

                Log::info('QB_NEW_TOKEN_CREATED');
                try{
                    QBConfig::create([
                        "user_id" => $this->data['user_id'],
                        "access_token" => $access_token,
                        "refresh_token" => $refresh_token,
                        "expires_in" => $expires_in,
                        "QBORealmID" => $config["QBORealmID"],
                        "client_id" => $config["client_id"],
                        "client_secret" => $config["client_secret"],

                    ]);
                }
                catch(\Exception $exception){
                    Log::info('QB_NEW_TOKEN_CREATE_DATABASE_SAVE'.$exception->getMessage());
                    return response()->json(["message" => "Refresh OAuth 2 Access token with Refresh Token failed", "code" => 400]);
                }

            }
            catch(\Exception $exception){
                Log::info('QB_NEW_TOKEN_CREATE'.$exception->getMessage());
                return response()->json(["message" => "Refresh OAuth 2 Access token with Refresh Token failed", "code" => 400]);
            }
        }
        $dataService = DataService::Configure(array(
            'auth_mode' => 'oauth2',
            'ClientID' => $config['client_id'],
            'ClientSecret' =>  $config['client_secret'],
            'RedirectURI' => $config['oauth_redirect_uri'],
            'scope' => $config['oauth_scope'],
            'baseUrl' => "development",
            'refreshToken' => $refresh_token,
            'accessTokenKey' => $access_token,
            'QBORealmID' => $config['QBORealmID'],
            "expires_in"=>  $expires_in
        ));

        Log::info('DATA SERVICE OBJECT CREATED SUCCESSFULLY');

        //$dataService->disableLog();
        $dataService->setLogLocation(storage_path('logs/quickbooks.log'));
        $path = storage_path('logs/quickbooks');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $dataService->setLogLocation($path);

        return $dataService;
        }

        public function refreshToken($config){
            try{
                $oauth2LoginHelper = new OAuth2LoginHelper($config['client_id'],$config['client_secret']);
                $newAccessTokenObj = $oauth2LoginHelper->refreshAccessTokenWithRefreshToken($config['refresh_token']);
            }
            catch(\Exception $exception){
                //Log::info($exception);
                throw $exception;
            }
            //$newAccessTokenObj->setRealmID($config['QBORealmID']);

            return $newAccessTokenObj;
        }
}
//$result = getDataService();

?>
