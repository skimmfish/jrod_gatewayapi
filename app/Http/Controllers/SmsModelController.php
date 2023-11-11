<?php

namespace App\Http\Controllers;

use App\Models\SmsModel;
use Illuminate\Http\Request;
use App\Http\Requests\SimModuleRequest;


class SmsModelController extends Controller
{

protected $api_ip_address, $ip_only,$api_username, $api_password,$mergedURL,$header,$sms_fetch_ip,$sms_stats;

public function __construct(){

    $this->ip_only = \App\Models\ConfigModel::get_conn_param('ip_only')['value'];
    $this->api_username = \App\Models\ConfigModel::get_conn_param('username')['value'];
    $this->api_password = \App\Models\ConfigModel::get_conn_param('password')['value'];

    //api_ip_address formed after retrieving the values of the api_username and password from the config_table
 $this->api_ip_address = \App\Models\ConfigModel::get_conn_param('api_port_ip')['value'].'/goip_send_sms.html?username='.$this->api_username.'&password='.$this->api_password;

 $this->sms_fetch_ip = \App\Models\ConfigModel::get_conn_param('api_port_ip')['value'].'/goip_get_sms.html?username='.$this->api_username.'&password='.$this->api_password.'&sms_num=0';

 $this->sms_stats =  \App\Models\ConfigModel::get_conn_param('api_port_ip')['value'].'/goip_get_sms_stat.html?username='.$this->api_username.'&password='.$this->api_password;
}


/**
 * This function fetches all sms sent to the modem via the sim cards
 *
     * @queryParam $port_id Integer Example: 1,2,3-8
     * @queryParam $id Integer $slots Example: 1
     * @queryParam $type String  Example: 0,1,2,3 or all
     *
     *
     * @response{
     * 'message':'success' or 'error'
     * }
     *
     * @header Connection close
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN
     * @header Host 54.179.122.227:52538
     */

public function get_sms_stat($port_id,$slots,$type){

/*
The specified $port_id (valued
from 1). The values are as follows:
1) all: all ports;
2) 2: Specify a single port;
3) 1-2, 4: Port numbers separated
by short numbers, specifying multiple ports, where "-" indicates a continuous port number;
*/


    /*statistic $type. The values are as
    follows:
    1) 0: The last hour;
    2) 1: The last two hours;
    3) 2: today;
    4) 3: cumulative;
    */

$body = [];

//affixing the remaining parts of the url to each other
$this->sms_stats = $this->sms_stats.'&ports='.$port_id.'&slots='.$slots.'&type='.$type;

try{

    $response = \App\Models\ConfigModel::callAPI('GET',$this->sms_stats,$body);

    $sms_counter = 0;

    return response()->json(['data'=>json_decode($response),'sms_counter'=>$sms_counter,'message'=>'Success'],200);

    }catch(\Exception $e){

    return response()->json(['data'=>null,'message'=>'error'],404);

    }
}

    /**
     * Displays all sms in the table
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN
     *
     * @response{
     * 'data': SMSModel $allSms object
     * 'message':'success' or 'error'
     * }

     */
    public function index()
    {
     $allSMS = \App\Models\SmsModel::all();
     return response()->json(['data'=>$allSMS,'message'=>'success'],200);
    }


    /***
     * Function retrieves messages by a particular sim card as saved in the database
     * @bodyParam $sim_num String Example: +125902920998
     *
     * @response{
     * 'data': []
     * 'message': string
     * }
     *
     * @header Connection close
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN
     */

    public function get_sms_by_sim_num($sim_num){
    try{

        $msgs = \App\Models\SmsModel::where(['msg_sender_no'=>$sim_num])->get();

    return response()->json(['data'=>$msgs],200);

}catch(\Exception $e){
    return response()->json(['data'=>NULL,'exception'=>$e->getMessage()],404);

}
}



    /***
     * Function retrieves messages by a particular sim card as saved in the database
     * @bodyParam $port_number Integer Example: 1-8
     * @response{
     * 'data': [],
     * 'status': string
     * }
     *
     * @header Connection close
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN
     */


public function get_sms_by_port_num($port_num){

    try{
        $msgs = \App\Models\SmsModel::where(['port_sent_from'=>$port_num])->get();
        return response()->json(['data'=>$msgs,'status'=>'success'],200);
        }catch(\Exception $e){
        return response()->json(['data'=>NULL,'status'=>'fail','exception'=>$e->getMessage()],404);

    }



}


/**
 * @queryParam SimModuleRequest $request
 *
 * @header Connection close
 * @header Accept * / *
 * @header Content-Type application/json;utf-8
 * @header Authorization Bearer AUTH_TOKEN
 *
 * form-data variable fetched via the Request object
 *
 * @bodyParam $sim_number String Example: 120390480
 *
 * @response{
 * 'data'=> [],
 *  'sim_no': String,
 * 'message': String
 * }
 *
 */
public function get_all_sms_for_sim_by_no_param(SimModuleRequest $request){

 $sim_number = $request->sim_number;

    try{
            $resultSet = \App\Models\SmsModel::where(['msg_sender_no'=>$sim_number])->orWhere(['sim_number_sent_to'=>$sim_number])->orderBy('created_at','DESC')->get();
            if(sizeof($resultSet)>0){
             return response()->json(['data'=>$resultSet,'sim_no'=>$sim_number,'message'=>'success'],200);
            }else{

             return response()->json(['data'=>NULL,'sim_no'=>$sim_number,'message'=>'Empty result set'],200);

            }
             }catch(\Exception $e){

                 return response()->json(['data'=>null,'error'=>$e->getMessage()],404);
             }
    }


    /**
     * Display the sms referenced by the id.
     *
     * @queryParam $id Integer Sms id in the database table
     *
     * @header Connection close
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN

     * @response{
     * 'data': \Illuminate\Http\Response $singleSms,
     * 'message': 'success'
     * }

     */
    public function show_sms($id)
    {
        try{
     $singleSms = \App\Models\SmsModel::findOrFail($id);

    return response()->json(['data'=>$singleSms,'message'=>'success'],200);

    }catch(\Exception $e){
        return response()->json(['data'=>NULL,'message'=>'error'],404);
    }
 }



    /**
     * Remove the specified resource from storage.
     *
     * @queryParam  \App\Models\SmsModel  $smsModel
     * @return \Illuminate\Http\Response
     */
    public function destroy(SmsModel $smsModel)
    {

    }

    /**
     * This function is to remove or change the state of an sms
     * @queryParam $id Integer Sms id in the database table

     * @header Connection close
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN

     * @response{
     *'message':'success' or 'error'
     * }
     */

    public function deleteResource($id){

    try{
    $smsRes = \App\Models\SmsModel::find($id);

    //deleting the resources here
    $smsRes->forceDelete();

    //$res =  \DB::delete("DELETE from sms_models WHERE id=?",[$id]);

    return response()->json(['message'=>'success'],200);

    }catch(\Exception $e){
    return response()->json(['error'=>$e->getMessage()],500);
    }
}

/**
 * This function sends bulk sms
 * @queryParam \illuminate\Http\Request $request
 *
 * @bodyParam _msg String Example: hello how are you doing today?
 * @bodyParam sim_port_number Integer Example: 1-8
 * @bodyParam recipient String[] Example: [+1234567788,+1352091093809,+2349076191416]
 *
 *
 * @request{
 *  '_msg': String $_msg Example: Hello how are you doing today?,
 *  'sim_port_number': Integer Example: 1-8,
 *  'recipient': [] Array Example: [+12302909991,+1290490339020]
 * }

* @header Connection keep-alive
* @header Accept * / *
* @header Content-Type application/octet-stream
* @header Authorization Bearer AUTH_TOKEN
* @header Host 54.179.122.227:52538
*
 * @response{
 * 'data': String
 * 'status': Response $res
 * }
 *
 */

public function send_bulk_sms(Request $request){

    //validating the requests sent from the mobile front_end
  $rules = [
    "_msg"=>['required','max:255','min:1'],
    "sim_port_number" => ['required','integer'],
    "recipient" => ['required','Array']
  ];

  //validating the rules
    $request->validate($rules);

    $body = [];
    $sender = null;
    //getting the feed from the front end form fields here
    $sms = $request->sms_msg;
    $sim_port_number = $request->sim_port_number;

    //save recipients in an array and split them later
    $recipients = ($request->recipient);

    //get the sim number that is sending the sms
    $sim = \App\Models\SimModule::where(['sim_port_number'=>$sim_port_number])->first();

    try{

    for($i=0;$i<count($recipients);$i++){

        //saving it in the database first of all
        $saveToDb = \App\Models\SmsModel::create([
        'sim_number_sent_to' => $recipients[$i],
        '_msg'=> $sms,
        'msg_sender_no'=> $sim->sim_number,
        'msg_activity_state' => 1,
        'msg_type'=>'outgoing',
        'active_state'=>true,
        'created_at'=>date('Y-m-d h:i:s',time()),
        'updated_at'=>date('Y-m-d h:i:s',time())
    ]);

    //forming the api call link with its parameters
    $this->mergedURL = $this->api_ip_address.'&recipients='.$recipients[$i].'&port='.$sim_port_number.'&sms='.rawurlencode($sms);

    $response = \App\Models\ConfigModel::callAPI('GET',$this->mergedURL,$body);

}

    return response()->json(['data'=>$response,'message'=>'success'],200);

    }catch(\Exception $e){

    return response()->json(
    ['data'=>null,
    'message'=>'error',
    'error'=>$e->getMessage()
    ],404);

}
}



/**
 * This function sends single sms
 * @queryParam \illuminate\Http\Request $request
 * @bodyParam _msg String Example: hello how are you doing today?
 * @bodyParam sim_port_number Integer Example: 1-8
 * @bodyParam recipient String Example: +1234567788

* @header Connection keep-alive
* @header Accept * / *
* @header Content-Type application/json;utf-8
* @header Authorization Bearer AUTH_TOKEN
* @header Host 54.179.122.227:52538

* @request{
 *  '_msg': String $_msg Example: Hello how are you doing today?,
 *  'sim_port_number': Integer Example: 1-8,
 *  'recipient': String Example: +1602901100
 * }
 *
 * @response{
 * 'data':'message',
 * 'status': Response $response
 * }
 *
 */
public function send_single_sms(\App\Http\Requests\SmsRequest $request){

    //validating the requests sent from the mobile front_end
    $request->validated($request->all());

    $body = [];
    $sender = null;

    //getting the feed from the front end form fields here
    $sms = $request->_msg;
    $sim_port_number = $request->sim_port_number;
    $recipient = $request->recipient;

    //get the sim number that is sending the sms
    $sim = \App\Models\SimModule::where(['sim_port_number'=>$sim_port_number])->first();

    if(!is_null($sim)){
        $sender = $sim->sim_number;
    }

    //saving it in the database first of all
    $saveToDb = \App\Models\SmsModel::create([
        'sim_number_sent_to'=>$request->recipient,
        '_msg'=> $sms,
        'msg_activity_state' => 1,
        'msg_sender_no'=>$sender,
        'msg_type'=>'outgoing',
        'active_state'=>true,
        'created_at'=>date('Y-m-d h:i:s',time()),
        'updated_at'=>date('Y-m-d h:i:s',time())
    ]);


    //forming the api call link with its parameters
     $this->mergedURL = $this->api_ip_address.'&port='.$sim_port_number.'&recipients='.$recipient.'&sms='.rawurlencode($sms);

    try{

    $response = \App\Models\ConfigModel::callAPI('GET',$this->mergedURL,$body);

    return response()->json(['data'=>json_decode($response),'message'=>'Success'],200);

    }catch(\Exception $e){

    return response()->json(['data'=>null,'message'=>'error'],404);

    }


}

    /**
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN

    * @bodyParam NULL
    * This function retrieves all the sms sent to the sim cards on all used ports on modem
    */

    public function get_all_sms(){

    //forming the api call link with its parameters
    //declaring the body
    $body = [];

    try{

    $response = \App\Models\ConfigModel::callAPI('GET',$this->sms_fetch_ip,$body);

    return response()->json(['data'=>json_decode($response),'message'=>'Success'],200);

    }catch(\Exception $e){

    return response()->json(['data'=>null,'message'=>'error'],404);

    }


}


/**
 * This function parses sms, and decodes it back into its original form using base64_decode()
 * @queryParam /Illuminate/Http/Request <$request>
 *
 *   * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN

 *
 * @request{
 * 'sms_content': String Example: How are you today?'
 * }
 * @response{
 * 'data': String $msg,
 * 'message': 'success' or 'error'
 * }
 *
 */
public function parse_sms(Request $request){

    //getting the sms_content in base64_encoded mode
    $sms_content = $request->sms_content;

    try{
    $msg = base64_decode($sms_content);
    return response()->json(['data'=>$msg,'message'=>'success'],200);
    }catch(\Exception $e){

        return response()->json(['data'=>NULL,'message'=>'error','error'=>$e->getMessage()],500);
    }
}


/**
   * This function changes the state of the sms
   * @header Connection keep-alive
   * @header Accept * / *
   * @header Content-Type application/octet-stream
   * @header Authorization Bearer AUTH_TOKEN

     *
     * @queryParam Integer $sms_id
     * @queryParam \Illuminate\Http\Request $request

     * @bodyParam new_state required Example: 1, or 2 or 3
     * @request{
     * 'sms_id': Integer $request->new_state Integer 1 = active,2=archived,3=deleted
     * }
     *
     * @response{
     * 'data': \Illuminate\Http\Response $response $res,
     * 'id': $sms_id Integer,
     * 'message': 'successful'
     * }
     */
    public function change_sms_state(Request $request,$sms_id){

       $state = $request->new_state; //1 = active, 2=archived, 3=deleted

       try{
       $sms = \App\Models\SmsModel::findOrFail($sms_id);
        if(!is_null($sms)){
            if($state==3){
                //delete if the state selected is 3 on the front-end
                $sms->delete();
            }else{
            $sms->active_state = $state;

            //saving the database
            $res = $sms->save();

            return response()->json(['data'=>$res,'id'=>$sms_id,'message'=>'success'],200);

        }
    }
    }catch(\Exception $r){
        return response()->json(['data'=>NULL,'message'=>'error'],404);
    }
    }

} //end of class

