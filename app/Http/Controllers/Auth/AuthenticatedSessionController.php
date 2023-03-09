<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Project;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use App\Models\Tokens;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Vinkla\Hashids\Facades\Hashids;


class AuthenticatedSessionController extends Controller
{


    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(LoginRequest $request)
    {

        $user = User::where(['email' => $request->email])->first();
        // dd($user);
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect']
            ]);
        }
/*
        if ($user->last_login_at == null and Carbon::now()->subDays(30) > $user->created_at) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect or not active or account is dormant ' ]
            ]);
        }
        if (($user->last_login_at != null and $user->last_login_at < Carbon::now()->subDays(30))) {
            //($user->last_login_at ==null and $user-> created_at > Carbon::now()->subDays(30) ) ){
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect or not active or account is dormant completely']
            ]);
        }
*/
        if (!Auth::attempt($request->only('email', 'password'))) {
                  throw ValidationException::withMessages([
                      'email' => ['The provided credentials are incorrect or do not exist']
                  ]);
              }


       // Log::info($project_id);
         
              /*
        if ($user->password_attempts >= getEnv("LOGIN_ATTEMPTS")) {
            $user->status = getEnv("BLOCKED_STATE");
            $user->save();
        }

        $user->password_attempts += 1;
        $user->save();
    */

        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect']
        ]);
    }

    /**
     * Handle an incoming api authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     *
     * @return \Illuminate\Http\Response
     */


    /**
     * Verifies user token.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function apiVerifyToken(Request $request)
    {
      Auth::guard('api')->check();

        $user = User::where('api_token', $request->token)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'token' => ['Invalid token']
            ]);
        }
        return response($user);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
