<?php
namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class Utils{
    static public  function getApiUser($request)
	{
        print_r($request->bearerToken());
        print_r(DB::table('api_tokens')->where('api_token', hash('sha256', $request->bearerToken()))->first());
		return DB::table('api_tokens')->where('api_token', hash('sha256', $request->bearerToken()))->first()->user_id;
	}
}

?>
