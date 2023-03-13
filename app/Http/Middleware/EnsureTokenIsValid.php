<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Auth::guard('api')->check();
        $user = DB::table('api_tokens')->where('api_token', hash('sha256', $request->bearerToken()))->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Api Authorization Failed',
                //'errors' => $data["dataService"]["message"],
                'code' => 404,
            ]);
        }
        return $next($request);
    }

       /**
     * Verifies user token.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function apiVerifyToken( $request)
    {
      //Auth::guard('api')->check();
      Hash::make($request->token);

        $user = DB::table('api_tokens')->where('api_token', hash('sha256', $request->bearerToken()))->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                //'status' => 401,
                'message' => 'QuickBooks Authorization Failed',
                //'errors' => $data["dataService"]["message"],
               // 'code' => $data["dataService"]["code"]
            ]);
        }
        return response($user);
    }
}
