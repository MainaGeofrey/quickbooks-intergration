<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users',
            'phone'   =>    ['required', 'regex:/^(\254|0)\d{9}$/','unique:users'],
            'password'   => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'password'   => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);
        return redirect(RouteServiceProvider::HOME);
    }


    /**
     * Handle an incoming api registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function apiStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            //'first_name' => 'required|string|max:255',
            //'last_name'  => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users',
            //'password'   => ['required', 'confirmed', Rules\Password::defaults()],
            'password'   => ['required', Rules\Password::defaults()],
        ]);

        if ($validator->fails()) {
            //Log::error($validator->errors()->all());
            $errors = $validator->errors()->all();
            Log::error($errors);
           return response()->json(["errors"=>$errors, "code"=>422]);
        }

        $token = Str::random(60);
        DB::beginTransaction();
        try {
            $user = User::create([
                //'first_name' => $request->first_name,
                //'last_name'  => $request->last_name,
                'email'      => $request->email,
                'password'   => Hash::make($request->password),
            ]);
            $token = ApiToken::create([
                'user_id' => $user->id,
                'api_token' => hash('sha256', $token),
            ]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["message"=>" User creation failed", "code"=>422]);

        }
        return response()->json(["token"=>$token->api_token, "code"=>200]);
    }
}
