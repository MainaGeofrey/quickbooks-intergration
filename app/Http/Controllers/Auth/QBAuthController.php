<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\QBAuthService;

class QBAuthController extends Controller
{
    //
    public function getToken(){
        $authObj = new QBAuthService();
       // $authObj->getTokens();
    }
}
