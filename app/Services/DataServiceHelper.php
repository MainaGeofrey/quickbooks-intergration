<?php
namespace App\Services;
//require_once(__DIR__ . '/../../vendor/autoload.php');
//require __DIR__.'/../../vendor/autoload.php';

use App\Helpers\Utils;

use App\Models\QBConfig;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\DataService;
use App\Services\QBAuthService;

//session_start();

class DataServiceHelper {

    protected $data;

    protected $config;

    public function __construct($data){
        $this->data = $data;
        $this->config = config("quickbooks");
    }

    public function getDataService(){
        $qb_token = QBConfig::where("user_id", $this->data["user_id"])->first();

        if($qb_token){
            //update config
            Log::info('QB_ACCESS_TOKEN_VALID');
            $access_token = $qb_token->access_token;
            $refresh_token = $qb_token->refresh_token;
            $expires_in = $qb_token->expires_in;

            try{
                $dataService = DataService::Configure(array(
                    'auth_mode' => 'oauth2',
                    'ClientID' => $qb_token->qb_client_id,
                    'ClientSecret' =>  $qb_token->client_secret,
                    'RedirectURI' => $this->config['oauth_redirect_uri'],
                    'scope' => $this->config['oauth_scope'],
                    'baseUrl' => $qb_token->base_url,
                    'refreshToken' => $refresh_token,
                    'accessTokenKey' => $access_token,
                    'QBORealmID' => $qb_token->realm_id,
                    "expires_in"=>  $expires_in
                ));
                Log::info("DataService | DATA SERVICE OBJECT CREATED SUCCESSFULLY  ");

                //$dataService->disableLog();
                $dataService->setLogLocation(storage_path('logs/quickbooks.log'));
                $path = storage_path('logs/quickbooks');
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }
                $dataService->setLogLocation($path);

                return $dataService;

            } catch (\Throwable $th) {
                Log::info("DataService | DATA SERVICE OBJECT NOT CREATED  ".json_encode($th->getMessage()));

                throw $th;

                //return ["message" => $th->getMessage(),"status" =>false, "code" => 200];
            }
        }
    }
    public function getValidQBConfig()
    {
        $qb_token = QBConfig::where("user_id", $this->data["user_id"])->first();


        if($qb_token){
            //update config
            $this->config["refresh_token"] = $qb_token->refresh_token;
            $this->config["qb_client_id"] = $qb_token->qb_client_id;
            $this->config["client_secret"] = $qb_token->client_secret;
            $this->config["QBORealmID"] = $qb_token->realm_id;

           // Log::info($config);

            $date1 = new DateTime(date('Y-m-d H:i:s',strtotime($qb_token->expires_in)));
            $date2 = new DateTime(date('Y-m-d H:i:s'));

            if( $date1 < $date2 ) {
                //token is expired
                Log::info("QB_TOKEN_EXPIRED");
                //$config["refresh_token"] = $qb_token->refresh_token;

                $newAccessTokenObj = $this->refreshToken($this->config);

                if(is_array($newAccessTokenObj)  && $newAccessTokenObj["code"] == 404){

                    return $newAccessTokenObj;
                }

                    try{
                        //$newAccessTokenObj = $newAccessTokenObj["access_obj"];
                        $access_token = $newAccessTokenObj->getAccessToken();
                        $refresh_token = $newAccessTokenObj->getRefreshToken();
                        $expires_in = $newAccessTokenObj->getAccessTokenExpiresAt();

                        Log::info("QB_ACCESS_TOKEN_UPDATED");

                        try{
                            Log::info("QB_ACCESS_TOKEN_UPDATED_DB_SAVE");
                          /*  QBConfig::create([
                                "user_id" => $this->data['user_id'],
                                "access_token" => $access_token,
                                "refresh_token" => $refresh_token,
                                "expires_in" => $expires_in,
                                "realm_id" => $config["QBORealmID"],
                                "qb_client_id" => $config["qb_client_id"],
                                "client_secret" => $config["client_secret"],

                            ]); */
                            DB::table('q_b_tokens')->where('user_id', $this->data['user_id'])->update([
                                'access_token' => $access_token,
                                'refresh_token' => $refresh_token,
                                'expires_in' => $expires_in,
                            ]);


                        }
                        catch(\Exception $exception){
                            Log::info("QB_ACCESS_TOKEN_UPDATED_DB_SAVE".$exception->getMessage());
                        }

                    }
                    catch(\Exception $exception){
                        //Log::info($exception->getMessage());
                        Log::info("QB_ACCESS_TOKEN_REFRESH_FAIL".$exception->getMessage());
                        return ["message" => $exception->getMessage(), "code" => 404];
                    }

            }
            else{
                //stored access token
                Log::info('QB_ACCESS_TOKEN_VALID');
                //$access_token = $qb_token->access_token;
                //$refresh_token = $qb_token->refresh_token;
                //$expires_in = $qb_token->expires_in;
                return ["message" => "Refresh Token Valid", "code" => 200];

            }
        }
        else{
            return ["message" => "Client QuickBooks Configuration Not Found", "code" => 404];
        }
     /*   try{
            $dataService = DataService::Configure(array(
                'auth_mode' => 'oauth2',
                'ClientID' => $qb_token->qb_client_id,
                'ClientSecret' =>  $qb_token->client_secret,
                'RedirectURI' => $this->config['oauth_redirect_uri'],
                'scope' => $this->config['oauth_scope'],
                'baseUrl' => $qb_token->base_url,
                'refreshToken' => $refresh_token,
                'accessTokenKey' => $access_token,
                'QBORealmID' => $qb_token->realm_id,
                "expires_in"=>  $expires_in
            ));
        Log::info('DATA SERVICE OBJECT CREATED SUCCESSFULLY');

        } catch (\Throwable $th) {

            Log::info("DataService | DATA SERVICE OBJECT NOT CREATED  ".json_encode($th->getMessage()));
            throw $th;

            //return ["message" => $th->getMessage(),"status" =>false, "code" => 200];
        } */

        //return $dataService;
        return ["access_obj" => $newAccessTokenObj, "code" => 200];
    }

        public function refreshToken($config){
            try{
                $oauth2LoginHelper = new OAuth2LoginHelper($config['qb_client_id'],$config['client_secret']);
                $newAccessTokenObj = $oauth2LoginHelper->refreshAccessTokenWithRefreshToken($config['refresh_token']);
            }
            catch(\Exception $exception){
                Log::info($exception);
                //throw $exception;
            return ["message" => $exception->getMessage(), "code" => 404];
            }
            //$newAccessTokenObj->setRealmID($config['QBORealmID']);

            return $newAccessTokenObj;
            //return ["access_obj" => $newAccessTokenObj, "code" => 200];
        }
}
//$result = getDataService();

?>
