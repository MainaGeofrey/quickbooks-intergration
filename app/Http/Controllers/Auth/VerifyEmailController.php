<?php

namespace App\Http\Controllers\Auth;

use App\Events\MyUserVerified;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Crypt;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Foundation\Auth\EmailVerificationRequest  $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Request $request)
    {
        $user = User::find($request->route('id'));

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME . '?verified=1');
        }
        return view('auth.set-password', compact('request', 'user'));
        //  return view('auth.confirm-password');
        //show the set password details here
        /*
        if ($user->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }
        return redirect()->route('home');
  return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
        return redirect($this->redirectPath())->with('verified', true);
*/
        /*
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
        */
    }
    public function store(Request $request)
    {
        $request->validate([
            //    'id'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $user = User::find($request->route('id'));
        // dd($user);
        if ($user->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }
        if ($user->forceFill([
            'password'       => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save()) {

            event(new MyUserVerified($user));

            return redirect()->intended(RouteServiceProvider::HOME . '?verified=1')->withSuccess('Your account has been errors. ');
        } else {
            return redirect()->intended(RouteServiceProvider::HOME . '?verified=1')->withError('Your account has been errors. ');
        }
    }
}
