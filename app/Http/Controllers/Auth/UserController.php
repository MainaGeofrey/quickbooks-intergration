<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use App\Models\Roles;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //

        $users =User::select('id', 'name','first_name', 'last_name', 'phone_number', 'email', 'email_verified_at', 'remember_token', 'created_at', 'updated_at', 'status')
        ->where("client_id", Utils::getClient($request))
        ->where('status' , '!=', 5)->with('roles')->get();

        return response()->json($users);

    }
    public function register(Request $request)
    {
    $validator = Validator::make($request->all(), [
        'name' => 'required|string',
        'email' => 'required',
        'first_name' => 'required|string',
        'last_name' => 'required|string',
        'phone_number' => 'required|string'

    ]);



    if ($validator->fails()) {
        $message = $validator->errors()->all();
        return response()->json(['success'=>false,"message"=>$message,"code" => 422]);
    }
    else{

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'client_id' => Utils::getClient($request),

        ]);
        if($user){
            if($request->role == 'super admin'){
                $role = Roles::find(1);
                $user->roles()->attach($role);
            }else{
                $role = Roles ::find(2);
                $user->roles()->attach($role);
            }
        }
        $user->save();
        event(new Registered($user));

        return response()->json([
            'success'=>true,"message"=>"User created successfully ", "code"=>200, "data"=>$user ]);
    }

    }

    public function login(Request $request){
        try{
            $validateUser = Validator::make($request->all(),
            [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if($validateUser->fails()){
                return response()->json(['status'=>false, 'message' =>'validation error'], 401);
            }
            if(!Auth::attempt($request->only(['email', 'password']))){
                return response()->json([
                    'status' => false,
                    'message' => 'Email & Password does not match with our record.',
                ], 401);
            }
            $user = User::where('email', $request->email)->first();
            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 200);

        }catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


    public function updateUser(Request $request)
    {
        // Log::error($request->all());
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'password' => 'string|required',
            'email_address' => 'required|email|unique:profiles|max:150',

        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->all();
            return response()->json(['success'=>true,"message"=>$message,"code" => 422]);
        }
        else{
            $user = User::find($request->id)->first();
            $user->update([
                'name' => $request->name,
                'email' => $request->email_address,
                'password' => Hash::make($request->password)
            ]);
            return response()->json([
                'success'=>true,"message"=>"User data updated successfully ", "code"=>200]);
        }

    }


    public function show( Request $request,$id)
    {
        //\
        // $role= DB::table('users as user')->where('user.id',  '=', $id);


        try{
            $user = User::where('id', $id)->select('id', 'name', 'email', 'first_name', 'last_name', 'phone_number','email_verified_at',  'created_at')
            ->where("client_id", Utils::getClient($request))
            ->with('roles')->first();
            $roles = $user->roles;
            // $role = '';
            foreach($roles as $user_role){
                Log::error($user_role);
                $role = $user_role;
            }
        //    $user_data= array_push($user, $role);
            // Log::error($user_data);

            Log::error($role->name);

            return response()->json(["data"=>$user, 'role' =>$role]);
        }
        catch (ModelNotFoundException $exception){
            return response()->json(["errors"=>"User Not found"]);
        }


    }
    public function showApi(Request $request)
    {

        //
        if($request){
            $id = $request->id;
            $user = User::where('id', $id)->select('id', 'name', 'email', 'email_verified_at', 'remember_token', 'created_at', 'updated_at')->get();
            return response()->json(['success'=>true,"message"=>"ok ","code"=>200, "data"=>$user]);
        }
        else{
            return response()->json(['success'=>true,"message"=>"Error, Provide relevant information ", "code"=>422]);
        }
    }


    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        //
        $user = User::find($request->id);
        $user->status = 5;
        $user->save();
        return true;


    }
}
