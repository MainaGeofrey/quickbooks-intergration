<?php
namespace App\Services;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Item;
use QuickBooksOnline\API\Facades\Payment;



//session_start();
class ItemServices {

    protected $dataService;
    public function __construct(){
        $dataService = new DataServiceHelper();
        $this->dataService = $dataService->getDataService();
    }
    public function store(){
        $payment = Item::create([
                "TrackQtyOnHand" => true,
                "Name" => "Garden Supplies",
                "QtyOnHand"=> 10,
                "IncomeAccountRef" => [
                  "name"=> "Sales of Product Income",
                  "value"=> "79"
                ],
                "AssetAccountRef"=> [
                  "name" => "Inventory Asset",
                  "value"=> "81"
                ],
                "InvStartDate"=> "2015-01-01",
                "Type" => "Inventory",
                "ExpenseAccountRef" => [
                  "name"=> "Cost of Goods Sold",
                  "value" => "80"
                ]
        ]);

        $result = $this->dataService->Add($payment);
        $result = json_encode($result, JSON_PRETTY_PRINT);
        print_r($result);
    }
}
