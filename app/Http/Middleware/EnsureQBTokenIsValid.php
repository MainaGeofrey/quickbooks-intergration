<?php

namespace App\Http\Middleware;

use App\Helpers\Utils;
use App\Providers\RouteServiceProvider;
use App\Services\DataServiceHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureQBTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */



    public function handle(Request $request, Closure $next): Response
    {
        $data["user_id"] =  Utils::getApiUser($request);
        $dataService = new DataServiceHelper($data);

        $data["dataService"] = $dataService->getValidQBConfig();

        if( $data["dataService"]["code"]== 404){
            return response()->json([
                'status' => false,
                'message' => 'QuickBooks Authorization Failed',
                'errors' => $data["dataService"]["message"],
                'code' => $data["dataService"]["code"]
            ]);
        }

        //$request['dataService'] = $data;
        return $next($request);
    }


    protected function redirectTo( $request): ?string
    {
        return $request->expectsJson() ? null : route('login');

    }
}
