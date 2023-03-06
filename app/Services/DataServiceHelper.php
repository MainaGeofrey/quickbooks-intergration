<?php
namespace App\Services;
//require_once(__DIR__ . '/../../vendor/autoload.php');
require __DIR__.'/../../vendor/autoload.php';

use App\Helpers\Utils;

use App\Models\QBConfig;
use DateTime;
use Illuminate\Support\Facades\DB;
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


        if($qb_token){
            //update config
            $config["refresh_token"] = $qb_token->refresh_token;
            $config["client_id"] = $qb_token->client_id;
            $config["client_secret"] = $qb_token->client_secret;
            $config["QBORealmID"] = $qb_token->realm_id;

           // Log::info($config);

            $date1 = new DateTime(date('Y-m-d H:i:s',strtotime($qb_token->expires_in)));
            $date2 = new DateTime(date('Y-m-d H:i:s'));

            if( $date1 < $date2 ) {
                //token is expired
                Log::info("QB_TOKEN_EXPIRED");
                //$config["refresh_token"] = $qb_token->refresh_token;

                $newAccessTokenObj = $this->refreshToken($config);

                    try{
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
                                "client_id" => $config["client_id"],
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
                        "realm_id" => $config["QBORealmID"],
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
        try{
            $dataService = DataService::Configure(array(
                'auth_mode' => 'oauth2',
                'ClientID' => $qb_token->client_id,
                'ClientSecret' =>  $qb_token->client_secret,
                'RedirectURI' => $config['oauth_redirect_uri'],
                'scope' => $config['oauth_scope'],
                'baseUrl' => $qb_token->base_url,
                'refreshToken' => $refresh_token,
                'accessTokenKey' => $access_token,
                'QBORealmID' => $qb_token->realm_id,
                "expires_in"=>  $expires_in
            ));
        Log::info('DATA SERVICE OBJECT CREATED SUCCESSFULLY');

        } catch (\Throwable $th) {
            Log::info("DataService | user data  ".__METHOD__."|".json_encode($config).json_encode($th->getMessage()));

            throw $th;

            //return ["message" => $th->getMessage(),"status" =>false, "code" => 200];
        }

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
