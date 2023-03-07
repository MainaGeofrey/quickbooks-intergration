<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use QuickBooksOnline\API\Facades\Customer;



//session_start();
class BalanceServices {

    protected $dataService;
    protected $data;
    public function __construct($data){
        $this->data = $data;
        $dataService = new DataServiceHelper($this->data);

        $this->dataService = $dataService->getDataService();
    }

    public function index(){
        $result = $this->dataService->Query("SELECT * FROM CustomerBalance");


        return $result;
    }


    public function show($data){
        $validator = Validator::make($data->all(), [
            'account_number' => 'required|string',
        ]);

        if($validator->fails()){

            return ["status"=>false,"message" => $validator->errors()->getMessages(), "code" => 422];
        }
        $customer = $this->dataService->Query("SELECT * FROM Customer WHERE DisplayName = '$data->account_number' ");
        if(!$customer){
            return ["message" => "Account number $data->account_number Not Found", "code" => 404];
        }

        $error = $this->dataService->getLastError();
        if ($error) {
            Log::info("LogBalance |Error|Request =>".json_encode($data)."|Error Response".$error->getHttpStatusCode()."|
                ".$error->getOAuthHelperError()."|".$error->getResponseBody());
            return ['status'=>false,'message'=>'We have received an Error'.$error->getIntuitErrorDetail(),'code'=>$error->getHttpStatusCode()];
        } else {

            return ['status'=>true,"customer_balance"=>$customer[0]->Balance, "code" => 200];
        }

     }

}


?>
