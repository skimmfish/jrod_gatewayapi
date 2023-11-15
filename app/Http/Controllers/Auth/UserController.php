<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;


class UserController extends Controller
{

    protected $rules;

    public function __construct()
    {

        $this->rules = [];
    }


    /**
     * This function logs in a user that has been verified previously
     * @queryParam \Illuminate\Http\Request $request
     *
     *
     * @header Accept-Language: en,
     * @header Accept:  application/json;utf-8,
     * @header Connection: keep-alive


     * @bodyParam   email_or_phone_number    string  required    The email/phone number of the  user.      Example: jayden@gm.co.uk / +1240291092
     * @bodyParam   password    string  required    The password of the  user.      Example: Rock1234@

     * @request{
     *  'email_or_phone_number':string,
     *  'pasword': string
     *
     *  }
     * @response{
     * "data": []
     * "message": string
     * "auth_token": "Bearer 3|7dUd16GpVXPwy530oqndmGXNDCShwYaQd2cXcp..........." for this only extract the token excluding the 'Bearer' string, pass this in the header to see sample response payload that is returned
     * }
     *
     */
    public function login_to_authenticate(Request $request){

        try{

    $v = $request->validate( [
        //'email'=>['required','email','string','min:3','max:255'],
        'password' => ['required','string','min:4']
    ]);

    //if(!\Auth::attempt(['username' => $userid, 'password' => $password])){

    //$auth = \Auth::attempt($request->only('email','password'));

    $usrA = \App\Models\User::where('email', $request->email_or_phone_number)->orWhere('phone_number', $request->email_or_phone_number)->first();

    if(!is_null($usrA)){

    if(\Auth::attempt(['email'=>$request->email_or_phone_number,'password'=>$request->password]) || (\Auth::attempt(['phone_number'=>$request->email_or_phone_number,'password'=>$request->password]))){

    //validating user....
    $user = \Auth::user();

   // \Auth::loginUsingId($user->id);

    return response()->json(["data"=>$user,
    'auth_token'=>'Bearer '.$user->createToken('Login API token on '.date('Y-m-d h:i:s',time()).' '.$user->email,
    $abilities = ['*'])->plainTextToken,
    'message'=>'success'],200);
    }

}else{

    //if credentials do not match
    return response()->json(['token'=>NULL, 'message'=>'Credentials do not match, User not found','data'=>NULL],404);
}

}catch(\Exception $e){

        return response()->json(['data'=>NULL,'message'=>'error','error'=>$e->getMessage()],500);

    }
}

    /**
     * This function logs out the currently authenticated user and clear the AUTH_TOKEN

     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN

     * @response{
     * "data": []
     * "message": string
     * }

 */
public function logout(Request $request){
try{

    Session::flush();
    $request->user()->token()->revoke();
    \Auth::guard('web')->logout();


    return response()->json(['message'=>'success'],200);

}catch(\Exception $e){
    return response()->json(['message'=>'error','error'=>$e->getMessage()],500);
}
}


     /**
     *
     * This function finds a user and return the user to move to another page to enter the new password and confirm it
     *
     * @bodyParam email string required maximum of 15 characters of Example: johnreid@gmail.com
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     *
     * @request{
     * 'email': String
     * }
     *
     * @response{
     * 'data': [],
     * 'message': String,
     * 'status': boolean
     *
     * }
     */

public function verify_usr_email(Request $request){

    try{
//        echo $request->email;
   $user =  \App\Models\User::where('email',$request->email)->first();

   if(!is_null($user)){

    $code = substr(str_shuffle("0123456789"), 0, 4);
    //send an email to the user

    //update the user's record with this code, after modification, reset the code back to 4 zeros
    \DB::update("UPDATE users SET otp_code=? WHERE email=?",[$code,$request->email]);

   \Mail::to($request->email)->send(new \App\Mail\send_password_modification_email($user->username,$code));

    return response()->json(['data'=>$user,'message'=>'user found, code sent to user','status'=>true],200);

   }

   //if the user is not found
   return response()->json(['data'=>NULL,'message'=>'user not found','status'=>false],200);

  }catch(\Exception $e){
        return response()->json(['data'=>null,'message'=>'error','error'=>$e->getMessage()],400);
  }
}

    /**
     * Create a new user saved in the database users table.
     *
     * @return \Illuminate\Http\Response
     * @queryParam \Illuminate\Http\Request $request
     *
     * @bodyParam   name String required The name of the user Example: solay
     * @bodyParam   username    string  required    The username of the  user.      Example: jayden
     * @bodyParam   email       string  required    The email Example: Rock1234@gmail.com
     * @bodyParam   password    string  required    The password of the  user, required to be confirmed.      Example: Rock1234@
     * @bodyParam   password_confirmation string required The password confirmation of the user registering or being registered Example: Rock1234@
     * @bodyParam   phone_number string required the phone number of the user example: +1239028109211
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8

     * @request{
     * "name": string,
     * "username": string,
    *  "email": string,
    *  "password": string,
    *  "password_confirmation": string,
    *  "phone_number": string

     * }

     * @response{
     * "data": $user information payload
     * "message": 'success' or 'error'
     * "auth_token": "Bearer 3|7dUd16GpVXPwy530oqndmGXNDCShwYaQd2cXcp..........." for this only extract the token excluding the 'Bearer' string, pass this in the header to see sample response payload that is returned
     * }

     */
    public function store_usr(Request $request)
    {

    try{
        $rules = [
        'name'=>['required','min:3','max:40'],
        'username'=>['required','string','min:6','max:12','unique:users'],
        'email'=>['required','string','unique:users'],
        'password'=>['required','string','min:6','max:15','confirmed'],
        'password_confirmation' => ['string','min:6','max:15'],
        'phone_number' => ['required','string','min:11','max:14']
        ];

        $messages = [

        ];

      //validating the entries
      $bagOfErrors = Validator::validate($request->all(),$rules,$messages);

       $user = \App\Models\User::create([
        'name'=>$request->name,
        'username'=>$request->username,
        'password'=> bcrypt($request->password),
        'email'=>$request->email,
        'phone_number'=>$request->phone_number,
        'created_at'=>date('Y-m-d h:i:s',time()),
        'updated_at'=>date('Y-m-d h:i:s',time()),
       ]);

       return response()->json(
        ['data'=>$user,
       'auth_token'=>'Bearer '.$user->createToken($request->username)->plainTextToken,
       'message'=>'success',
        ],200);
    }catch(\Exception $e){

     return response()->json(['data'=>NULL,'message'=>'error','error'=>$e->getMessage()],500);

    }
    }



     /**
     * Update the user's password, this function doesn't require authentication token
     * The username and email field is non-updateable to avoid conflicts with other users
     * @return \Illuminate\Http\Response
     *
     * @bodyParam password String required Example: johnreid
     * @bodyParam password_confirmation required Example: johnreid
     * @bodyParam email String required maximum of 15 characters of Example: JoyKam
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8

     * @response{
     * 'data': []
     * 'message': String
     * }
     */

    public function update_usr_password(Request $request){

        $rules = [
            'email'=>['required','email','max:255',],
            'password'=>['required','string','min:6','max:15','confirmed'],
            'password_confirmation' => ['required','string','min:6','max:15']
        ];

        try{
        //running validation for the fields
        $request->validate($rules);
        $email = $request->email;

        //finding the user

        $user = \App\Models\User::where(['email'=>$email])->first();

        if(!is_null($user)){

        $nwpassword = bcrypt($request->password);

        //otp_last used...
        $lastUsed = date('Y-m-d h:i:s');

        \DB::update("UPDATE users SET password=?, otp_code=?, code_last_used=? WHERE email=?",[$nwpassword,NULL,$lastUsed,$email]);

    }

        return response()->json(['data'=>$user,'messasge'=>'success'],200);

        }catch(\Exception $e){

            return response()->json(['data'=>null,'message'=>'error','error'=>$e->getMessage()],500);

        }

  }



  /**
   * This function confirms otp code before navigation to the next screen to enter the new password
   *
   * @bodyParam otp_code String  Example: 1234
   * @bodyParam email String  Example: jro@gmail.co.uk

   * @header Connection keep-alive
   * @header Accept * / *
   * @header Content-Type application/json;utf-8

   * @request{
   *
   *  'otp_code':1234,
   *  'email': $email

   * }
   *
   * @response{
   * 'data': boolean
   * 'message': string
   * }
   *
   */
  public function confirm_otp_code(Request $req){

    $rule = [
    'otp_code'=>['required','max:4','min:4'],
    'email'=>['required','max:255','min:3']
    ];

    try{
    $req->validate($rule);
    //retrieving the otp code from the request body
    $otp_code = $req->otp_code;

    $email = $req->email;

   $confirmValidOtp = \App\Models\User::where(['otp_code'=>$otp_code,
   'email'=>$email])->first();
   if(!is_null($confirmValidOtp)){
    return response()->json([
        'data'=>true,"message"=>'otp_is_valid'],200);
   }
    return response()->json([
        'data'=>false,'message'=>'otp_is_invalid'
    ],200);

    }catch(\Exception $e){
        return response()->json(['data'=>'error','error'=>$e->getMessage()],400);
    }
  }


    /**
     * This function gets user profile
     *
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     *
     * @response{
     * 'data': [],
     * 'message': string
     * }

     */

    public function get_user_profile(){

        try{

            if(\Auth::check()){
            $user = \App\Models\User::where('id',\Auth::user()->id)->first();

            if(!is_null($user)){
                return response()->json(['data'=>$user,'message'=>'success'],200);
            }else{

                return response()->json(['data'=>null,'message'=>'User not found'],200);
            }
        }

        }catch(\Exception $e){
            return response()->json(['data'=>null,'message'=>'error','error'=>$e->getMessage()],500);
        }


    }


    /**
     * Update the specified resource in storage.
     * The username and email field is non-updateable to avoid conflicts with other users
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * @bodyParam name String required Example: johnreid
     * @bodyParam password String required maximum of 15 characters of Example: JoyKam
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN

     * @response{
     * 'data': \App\Models\User $userModified,
     * 'message':'success' or 'error'
     * }
     */
    public function updatepassword(Request $request, $id)
    {

        $rules = [
            'name'=>['required','min:3','max:40'],
            'password'=>['required','string','min:6','max:15','confirmed']
        ];

        try{
        //running validation for the fields
        $request->validate($rules);

        //finding the user

        $user = \App\Models\User::findOrFail($id);
        $user->name = $request->name;
        $user->password = bcrypt($request->password);

        //saving the info
        $userModified = $user->save();

        return response()->json(['data'=>$userModified,'messasge'=>'success'],200);
        }catch(\Exception $e){
            return response()->json(['data'=>null,'message'=>'error','error'=>$e->getMessage()],500);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    /**this function parses_a bcrypted phrase or string
     * @param \Illuminate\Http\Request
     * @bodyParam \Illuminate\Http\Request
     */
public function parse_password(Request $request){

$hashed = bcrypt($request->password);

return response()->json(['data'=>$hashed],200);

}
}
