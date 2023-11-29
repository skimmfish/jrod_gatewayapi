<?php

namespace App\Http\Controllers;

use App\Models\SmsModel;
use Illuminate\Http\Request;
use App\Http\Requests\SimModuleRequest;
use Illuminate\Support\Facades\Http;

class SmsModelController extends Controller
{

protected $api_ip_address, $api_ip_address_v2, $ip_only,$api_username, $api_password,$mergedURL,$header,$sms_fetch_ip,$sms_stats;

public function __construct(){

    $this->ip_only = \App\Models\ConfigModel::get_conn_param('ip_only')['value'];
    $this->api_username = \App\Models\ConfigModel::get_conn_param('username')['value'];
    $this->api_password = \App\Models\ConfigModel::get_conn_param('password')['value'];

    //api_ip_address formed after retrieving the values of the api_username and password from the config_table
 $this->api_ip_address = \App\Models\ConfigModel::get_conn_param('api_port_ip')['value'].'/goip_send_sms.html?username='.$this->api_username.'&password='.$this->api_password;

 //this is for the v2 of the API endpoint for sending SMS both single and multiple recipients

 $this->api_ip_address_v2 = \App\Models\ConfigModel::get_conn_param('api_port_ip')['value'].'/goip_post_sms.html?username='.$this->api_username.'&password='.$this->api_password;

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


    /*static $type. The values are as
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
        $msgs = \App\Models\SmsModel::where(['port_sent_from'=>$port_num])->orWhere('port_received_at',$port_num)->get();

        return response()->json(['data'=>$msgs,'status'=>'success'],200);

    }catch(\Exception $e){

        return response()->json(['data'=>NULL,'status'=>'fail','exception'=>$e->getMessage()],404);

    }
}


/**
 * This function fetches all sms from modem gateway
 */
public function fetch_sms_from_gateway(){

    //forming the api call link with its parameters
    //declaring the body
    $body = [];
            //fetching the simNumber
            $simNumber = new \App\Http\Controllers\SimModuleController;

    try{

        $response = \App\Models\ConfigModel::callAPI('GET',$this->sms_fetch_ip,$body);

        $res = json_decode($response);
        $sizeofSMS = sizeof($res->data);

        $reformedArr = array();
        for($i=0;$i< $sizeofSMS;$i++){

           $portNum = explode(".",$res->data[$i][1])[0];

        //retrieving the sim number msg was sent to
           $simNo = $simNumber->get_simnumber_by_port_id($portNum);

            $smsMessage = $res->data[$i][5];

            //decoding the timestamp the sms was sent
           $timeStamp = $res->data[$i][2];

           //retrieving the sender
           $sender = $res->data[$i][3];

           //searching if this message has been saved previously
           $search  = \App\Models\SmsModel::where(['incoming_timestamp'=>$timeStamp,'port_received_at'=>$portNum,'sim_number_sent_to'=>$simNo])->first();

           if(is_null($search)){
           //setup message saving in the DB
            $cr = \App\Models\SmsModel::create([
                'port_received_at' => $portNum,
                'sim_number_sent_to'=>$simNo,
                '_msg' => $smsMessage,
                'msg_type'=>'incoming',
                'msg_activity_state '=>1,
                'active_state' => true,
                'msg_sender_no'=> $sender,
                'created_at' => date('Y-m-d h:i:s',time()),
                'incoming_timestamp' =>$timeStamp
            ]);

        }
        }

    }catch(\Exception $e){

    return response()->json(['data'=>null,'message'=>'error', 'error'=>$e->getMessage()],404);

    }


}

/**
 * This function fetches messages by proding the skyline modem at every 50ms
 *
 * @header Content-Type text/event-stream
 * @header Connection close
 * @header Accept * / *
 * @header Authorization Bearer AUTH_TOKEN
 *
 */
public function stream(){

    $latestSms = NULL;

    return response()->stream(function(){

        while(true){
           // $curDate = date('Y-m-d h:i:s',time());

            //latest sms on the gateway modem
            $this->fetch_sms_from_gateway();
            $latestSms = \App\Models\SmsModel::latest()->get();

            if($latestSms){

            echo 'data: {"most_recent_sms":"' . base64_decode($latestSms->_msg) . '"sent_to":"'. $latestSms->sim_number_sent_to.'}' . "\n\n";

        }

                ob_flush();
                flush();

                // Break the loop if the client aborted the connection (closed the page)
                if (connection_aborted()) {break;}
                usleep(50000); // 50ms
            }
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
        ]);

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

* @header Connection close
* @header Accept * / *
* @header Content-Type application/json
* @header Authorization Bearer AUTH_TOKEN
* @header Host 54.179.122.227:52538
* @header charset utf-8
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
    "_msg"=>['required','string','max:255','min:3'],
    "sim_port_number" => ['required','integer'],
    "recipient" => ['required','Array']
  ];

  //validating the rules
    $request->validate($rules);

    $body = [];
    $sender = null;

    //getting the feed from the front end form fields here
    $sms = $request->_msg;
    $sim_port_number = $request->sim_port_number;
    $recipients = ($request->recipient);


    //this is to hold all recipients
    $allRecipients = NULL;

    //get the sim number that is sending the sms
    $sim = \App\Models\SimModule::where(['sim_port_number'=>$sim_port_number])->first();

    try{

    $allRecipients = implode(",",$recipients);

    //forming the api call link with its parameters
    $this->mergedURL = $this->api_ip_address.'&recipients='.$allRecipients.'&charset=Utf-8&port='.$sim_port_number.'&sms='.rawurlencode($sms);

    $response = \App\Models\ConfigModel::callAPI('GET',$this->mergedURL,$body);

    return response()->json(['data'=>json_decode($response),'message'=>'success'],200);

    }catch(\Exception $e){
    return response()->json(['data'=>null,'message'=>'error','error'=>$e->getMessage()],404);
}

}

/**
* This function sends single sms
*
* @bodyParam _msg String Example: hello how are you doing today?
* @bodyParam sim_port_number Integer Example: 1-8
* @bodyParam recipient String Example: +1234567788

* @header Connection keep-alive
* @header Accept * / *
* @header Content-Type application/json;utf-8
* @header Authorization Bearer AUTH_TOKEN

* @request{
 *  '_msg': String $_msg Example: Hello how are you doing today?,
 *  'sim_port_number': Integer Example: 1-8,
 *  'recipient': String Example: +1602901100
 * }
 *
 * @response{
 * 'data': string,
 * 'status': Response $response
 * }
 *
 */

public function send_single_sms(Request $request){

    $rule =  [
    'recipient'=>['required',"string"],
    '_msg'=>['required',"string"],
    'sim_port_number'=> ['required',"integer"]
    ];

    //validating the requests sent from the mobile front_end
    $request->validate($rule);

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
     $this->mergedURL = $this->api_ip_address.'&port='.$sim_port_number.'&charset=Utf-8&recipients='.$recipient.'&sms='.rawurlencode($sms);

    try{

    $response = \App\Models\ConfigModel::callAPI('get',$this->mergedURL,$body);

    return response()->json(['data'=>json_decode($response),'message'=>'Success'],200);

    }catch(\Exception $e){

    return response()->json(['data'=>null,'message'=>'error'],404);

    }


}


/**
 * This function sends sms to the phone number via the sim port number
 * @param sim_port_number Integer Example: 1,2,3,4
 * @param recipient String Example: +12490293092
 * @param sms String Example: sms sent to the number
 */
public function send_sms($sim_port_number,$recipient,$sms){

        //forming the api call link with its parameters
        try{
        $this->mergedURL = $this->api_ip_address.'&port='.$sim_port_number.'&charset=Utf-8&recipients='.$recipient.'&sms='.rawurlencode($sms);

      return $response = \App\Models\ConfigModel::callAPI('get',$this->mergedURL,[]);
        }catch(\Exception $e){
            return $e->getMessage();
        }
}


/**
* This function sends single sms
*
* @bodyParam _msg String Example: hello how are you doing today?
* @bodyParam sim_port_number Integer Example: 1-8
* @bodyParam recipient String Example: +1234567788

* @header Connection keep-alive
* @header Accept * / *
* @header Content-Type application/json;charset=utf-8
* @header Authorization Bearer AUTH_TOKEN

* @request{
 *  '_msg': String $_msg Example: Hello how are you doing today?,
 *  'sim_port_number': Integer Example: 1-8,
 *  'recipient': String Example: +1602901100
 * }
 *
 * @response{
 * 'data': string,
 * 'status': Response $response
 * }
 *
 */

 public function send_single_sms_v2(Request $request){

    $rule =  [
    'recipient'=>['required',"string"],
    '_msg'=>['required',"string"],
    'sim_port_number'=> ['required',"integer"]
    ];

    //validating the requests sent from the mobile front_end
    $request->validate($rule);

    $body = [];
    $sender = null;

    //getting the feed from the front end form fields here
    $sms = $request->_msg;
    $sim_port_number = $request->sim_port_number;
    $recipient = $request->recipient;


    //forming the api call link with its parameters
     $this->mergedURL = $this->api_ip_address_v2.'&from=1&to=2&port='.$sim_port_number.'&recipients='.$recipient.'&sms='.rawurlencode($sms);

    try{

    $response = \App\Models\ConfigModel::callAPI('get',$this->mergedURL,$body);

        //get the sim number that is sending the sms
        $sim = \App\Models\SimModule::where(['sim_port_number'=>$sim_port_number])->first();

        if(!is_null($sim)){
            $sender = $sim->sim_number;
        }
/*
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
*/

    return response()->json(['data'=>json_decode($response),'message'=>'Success'],200);

    }catch(\Exception $e){

    return response()->json(['data'=>null,'message'=>'error'],404);

    }


}


/**

     * This function retrieves all the sms sent to the sim cards on all used ports on modem
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN
    *
    */

    public function get_all_sms(Request $request){

    //forming the api call link with its parameters
    //declaring the body
    $body = [];
            //fetching the simNumber
            $simNumber = new \App\Http\Controllers\SimModuleController;

    try{

        $response = \App\Models\ConfigModel::callAPI('GET',$this->sms_fetch_ip,$body);

        $res = json_decode($response);
        $sizeofSMS = sizeof($res->data);

        $reformedArr = array();
        for($i=0;$i< $sizeofSMS;$i++){

           $portNum = explode(".",$res->data[$i][1])[0];

        //retrieving the sim number msg was sent to
           $simNo = $simNumber->get_simnumber_by_port_id($portNum);

            $smsMessage = $res->data[$i][5];

           $timeStamp = $res->data[$i][2];

           //retrieving the sender
            $sender = $res->data[$i][3];

           //searching if this message has been saved previously
           $search  = \App\Models\SmsModel::where(['incoming_timestamp'=>$timeStamp,'port_received_at'=>$portNum,'sim_number_sent_to'=>$simNo])->first();

           if(is_null($search)){
           //setup message saving in the DB
            $cr = \App\Models\SmsModel::create([
                'port_received_at' => $portNum,
                'sim_number_sent_to'=>$simNo,
                '_msg' => $smsMessage,
                'msg_type'=>'incoming',
                'msg_activity_state '=>1,
                'msg_sender_no' => $sender,
                'active_state' => true,
                'created_at' => date('Y-m-d h:i:s',time()),
                'incoming_timestamp' =>$timeStamp
            ]);

        }
        }

//        print_r($res->data[4][1]);


   return response()->json(['data'=>json_decode($response),'message'=>'Success'],200);

    }catch(\Exception $e){

    return response()->json(['data'=>null,'message'=>'error', 'error'=>$e->getMessage()],404);

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

