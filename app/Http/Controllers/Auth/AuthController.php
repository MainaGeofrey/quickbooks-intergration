<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Http\Requests\Auth\LoginRequest;


class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
            // Log::info($request->header('attestation'));
        $data = json_decode($request ->getContent(),true);


            $validator = Validator::make($data,
            array(
                'email' => 'required|email',
                'password' => 'required',

            ));

            if ($validator->fails()) {
                Log::error($validator->errors()->all());
                throw ValidationException::withMessages([
             'email' => ['The provided credentials are incorrect']
         ]);
                        }
                        if (!Auth::attempt($request->only('email', 'password'))) {
                                  throw ValidationException::withMessages([
                                      'email' => ['The provided credentials are incorrect or do not exist']
                                  ]);
                              }
                              $user = User::where('email', $request->email)->first();
                          /*
                          return response([
          'user' => auth()->user(),
          'access_token' => auth()->user()->createToken('authToken')->plainTextToken
      ], Response::HTTP_OK);

      {
    "id": 2,
    "first_name": "German",
    "last_name": "Stark",
    "email": "admin@demo.com",
    "email_verified_at": "2022-07-14T11:37:39.000000Z",
    "created_at": "2022-07-14T11:37:39.000000Z",
    "updated_at": "2022-07-14T11:37:39.000000Z",
    "api_token": "$2y$10$lzYGs3CVjxdlR2ERLfZOyezaXM8qXLGd5fHEkjoBmDxznEl.CvAdC"
}
{
    "id": 2,
    "first_name": "German",
    "last_name": "Stark",
    "email": "admin@demo.com",
    "email_verified_at": "2022-07-14T11:37:39.000000Z",
    "created_at": "2022-07-14T11:37:39.000000Z",
    "updated_at": "2022-07-14T11:37:39.000000Z",
    "api_token": "$2y$10$lzYGs3CVjxdlR2ERLfZOyezaXM8qXLGd5fHEkjoBmDxznEl.CvAdC"
}

{
    "id": 1,
    "name": "mechanical",
    "email": "johngichuhi769@gmail.com",
    "email_verified_at": "2022-09-01T14:38:38.000000Z",
    "two_factor_secret": null,
    "two_factor_recovery_codes": null,
    "two_factor_confirmed_at": null,
    "created_at": "2022-09-01T10:15:48.000000Z",
    "updated_at": "2022-09-01T10:15:48.000000Z"
}
*/
return response([
'id' => auth()->user()->id,
"name" =>auth()->user()->name,
'api_token' => auth()->user()->createToken('authToken')->plainTextToken
], Response::HTTP_OK);

                                    return response($user);

    }
    public function apiVerifyToken(Request $request)
{
    $request->validate([
        'api_token' => 'required'
    ]);

    $user = User::where('api_token', $request->api_token)->first();

    if(!$user){
        throw ValidationException::withMessages([
            'token' => ['Invalid token']
        ]);
    }
    return response($user);
}
}
