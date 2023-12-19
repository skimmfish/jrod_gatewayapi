<?php
namespace App\Http\Controllers;
use App\Models\SmsModel;
use Illuminate\Http\Request;
use App\Http\Requests\SimModuleRequest;
use Illuminate\Support\Facades\Http;
use App\Events\NewSms;
use Laravel\Sanctum\PersonalAccessToken;


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


    /**
     * This function fetches sms for each phone number in a thread of sms sent/or received  to that particular number
     * @queryParam recipient String Example: +123920190929
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN

     *
     */
    public function view_sms_thread($recipient){

        try{

            $msgThread = \App\Models\SmsModel::where(['sim_number_sent_to'=>$recipient])->whereNotNull('_msg')->get();
            if(sizeof($msgThread)>0){
            return response()->json(['data'=>$msgThread,'status'=>'success','message'=>'sms_thread_retrieved'],200);
            }else{
                return response()->json(['data'=>NULL,'status'=>'success','message'=>'no_thread_for_recipient'],200);
            }

        }catch(\Exception $e){
            return response()->json(['data'=>null,'status'=>'fail','message'=>'error: '.$e->getMessage()],400);
        }
    }

    /***
     * Function retrieves messages by a particular sim card as saved in the database
     * @bodyParam sim_num String Example: +125902920998
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

        $msgThread = \App\Models\SmsModel::where(['sim_number_sent_to'=>$sim_num])->whereNotNull('_msg')->get();
        if(sizeof($msgThread)>0){
        return response()->json(['data'=>$msgThread,'status'=>'success','message'=>'sms_thread_retrieved'],200);
        }else{
            return response()->json(['data'=>NULL,'status'=>'success','message'=>'no_thread_for_recipient'],200);
        }

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

        //fire the NewSmsMessage Dispatch
//        NewSms::dispatch();

      $msgs = \App\Models\SmsModel::distinct()->select('sim_number_sent_to','port_sent_from','read_status','_msg','created_at')->where(['port_sent_from'=>$port_num])->whereNotNull('_msg')->orderBy('created_at','DESC')->get();

      $msg = \App\Models\SmsModel::distinct()->select('sim_number_sent_to','port_sent_from','read_status','_msg','created_at')->where(['port_sent_from'=>$port_num])->whereNotNull('_msg')->orderBy('created_at','DESC')->get();


            //converting the messages into a unique associative array
          $msg = collect($msgs)->unique('sim_number_sent_to')->toArray();
        //$msg = $msg.toArray();

        return response()->json(['data'=> array_values($msg),'status'=>'success'],200);

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

        //print_r($response);

        $res = json_decode($response);
        $sizeofSMS = sizeof($res->data);

        $reformedArr = array();

        for($i=0;$i<$sizeofSMS;$i++){

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
                '_msg' => base64_decode($smsMessage),
                'msg_type'=>'incoming',
                'msg_activity_state '=>1,
                'active_state' => true,
                'msg_sender_no'=> $sender,
                'created_at' => date('Y-m-d h:i:s',$timeStamp),
                'incoming_timestamp' =>$timeStamp
            ]);

        }
        }

        return response()->json(['data'=>$res,'message'=>'sms_fetched_successfully','status'=>'success'],200);

    }catch(\Exception $e){

    return response()->json(['data'=>null,'message'=>'error', 'error'=>$e->getMessage()],404);

    }
}

/**
 * This function tests if the broadcast is sent successfully
 *
 */
public function get_broadcast(){

return event(new \App\Events\NewSms('Hello broadcasting now!'));
}


public function getsm(){

return $latestSms = \App\Models\SmsModel::where(['push_status'=>false,'read_status'=>false,'msg_type'=>'incoming'])->get()->last();

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
public function gtstream(){

    $latestSms = NULL;
    $fetchedSms = array();

    return response()->stream(function(){

        while(true){

            //latest sms on the gateway modem
            $this->fetch_sms_from_gateway();

            $latestSms = \App\Models\SmsModel::where(['push_status'=>false,'read_status'=>false,'msg_type'=>'incoming'])->get();

            if(sizeof($latestSms)>0){

                foreach($latestSms as $x){

              \Log::info('data: {"New Sms from "' .$x->msg_sender_no. '":"' . $x->_msg . '", "sent_to":"' . $x->sim_number_sent_to . '"}"');

            //call the pushnotification function from here
                $pushStatus = $this->sendPushNotification();

               \Log::info($pushStatus);

               //we break out of the current thread to proceed
               break;

                //update the push status for the message
                $sms = \App\Models\SmsModel::findOrFail($x->id);
                $sms->push_status = true;
                $sms->updated_at = date('Y-m-d h:i:s',time());
                $sms->save();

            } //end of foreach loop

            break;
       }

                    continue;

                ob_flush();
                flush();

                // Break the loop if the client aborted the connection (closed the page)
                if (connection_aborted()) {break;}
                usleep(50000); // 50ms
                } //end of while loop

        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream'
        ]);

}


    /**
     * This function sends push notification to the app each time there is a new sms
     *
     */
    public function sendPushNotification(){

        $fcmToken = \App\Models\User::where(['id'=>11])->pluck('fcm_token_key')->first();

        $SERVER_KEY = env('FCM_SERVER_KEY');

//        \Log::info("fcm_token:".$fcmToken);
  //      \Log::info("server_key: ".$SERVER_KEY);

        try{
        //get the last sms from the DB for incoming SMS
       $latestSms = \App\Models\SmsModel::where(['push_status'=>false,'read_status'=>false,'msg_type'=>'incoming'])->get()->last();

        if($latestSms){

                $data = [
                "registration_ids" => array($fcmToken),
                "notification" => [
                    "title" => "New SMS Notification",
                    "body" => $latestSms->_msg,
                ]
            ];

            $res = json_encode($data);

            $headers = [
                'Authorization: key=' . $SERVER_KEY,
                'Content-Type: application/json',
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $res);

            $response = curl_exec($ch);

           // \Log::info($response);

            //update the sms's push_status
            $latestSms->push_status = true;
            $latestSms->save();

            //print_r($response);
            return response()->json(['data'=>$response,'status'=>'success'],200);
        }

    }catch(\Exception $e){
    return response()->json(['data'=>NULL,'status'=>'fail','error'=>$e->getMessage()],500);
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

     //modifying the read_status of the sms after it has been read
     $singleSms->read_status = true;

     $singleSms->save();

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


    $response = array();

    //this is to hold all recipients
    $allRecipients = NULL;

    //get the sim number that is sending the sms
    $sim = \App\Models\SimModule::where(['sim_port_number'=>$sim_port_number])->first();

    try{

    $allRecipients = implode(",",$recipients);

    //forming the api call link with its parameters
    $this->mergedURL = $this->api_ip_address.'&recipients='.$allRecipients.'&charset=Utf-8&port='.$sim_port_number.'&sms='.rawurlencode($sms);

    $sender = \App\Models\SimModule::where(['sim_port_number'=>$sim_port_number])->pluck('sim_number')->first();

    //sending the sms here
    $response = \App\Models\ConfigModel::callAPI('GET',$this->mergedURL,$body);

        //decoding the response payload into a string
$dat = json_decode($response);

if($response=='connection_failure'){
    return response()->json(['data'=>null,'message'=>'fail_on_connection_failure'],500);
    }elseif($dat->message=='Not Registered'){
        return response()->json(['data'=>null,'message'=>'sim_not_registered'],500);
    }elseif($dat->code==5){
        return response()->json(['data'=>[],'message'=>'msg_not_sent'],500);
    }else if($dat->code==6){
        return response()->json(['data'=>[],'message'=>'session_timed_out'],500);
    }

    for($i=0;$i<sizeof($recipients);$i++){

        //saving it in the database first of all
        $saveToDb = \App\Models\SmsModel::create([
            'sim_number_sent_to'=>$recipients[$i],
            '_msg'=> $sms,
            'group_status'=>'bulk',
            'msg_activity_state' => 1,
            'msg_sender_no'=>$sender,
            'msg_type'=>'outgoing',
            'active_state'=>true,
            'port_sent_from'=>$sim_port_number,
            'created_at'=>date('Y-m-d h:i:s',time()),
            'updated_at'=>date('Y-m-d h:i:s',time())
        ]);

        }

    return response()->json(['data'=>$dat,'message'=>'success'],200);

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

    $response = array();

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

    //forming the api call link with its parameters
     $this->mergedURL = $this->api_ip_address.'&port='.$sim_port_number.'&charset=Utf-8&recipients='.$recipient.'&sms='.rawurlencode($sms);

    try{

    $response = \App\Models\ConfigModel::callAPI('get',$this->mergedURL,$body);

        //decoding the response payload into a string
        $dat = json_decode($response);


        if($response=='connection_failure'){
            return response()->json(['data'=>null,'message'=>'fail_on_connection_failure'],500);
            }elseif($dat->code==5){
                if($dat->message){
                if($dat->message=='Not Registered'){
                return response()->json(['data'=>null,'message'=>'sim_not_registered_msg_not_sent'],500);
            }
        }
    }else if($dat->code==6){
        return response()->json(['data'=>[],'message'=>'session_timed_out'],500);
    }

        //saving it in the database first of all
        $saveToDb = \App\Models\SmsModel::create([
            'sim_number_sent_to'=>$request->recipient,
            '_msg'=> $sms,
            'msg_activity_state' => 1,
            'msg_sender_no'=>$sender,
            'msg_type'=>'outgoing',
            'active_state'=>true,
            'port_sent_from'=>$sim_port_number,
            'group_status'=>'single',
            'created_at'=>date('Y-m-d h:i:s',time()),
            'updated_at'=>date('Y-m-d h:i:s',time())
        ]);

            return response()->json(['data'=>$dat,'message'=>'success'],200);

            }catch(\Exception $e){
            return response()->json(['data'=>null,'message'=>'error','error'=>$e->getMessage()],404);
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

        $sizeofSMS = 0;
    //forming the api call link with its parameters
    //declaring the body
    $body = [];
            //fetching the simNumber
            $simNumber = new \App\Http\Controllers\SimModuleController;

    try{

        $response = \App\Models\ConfigModel::callAPI('GET',$this->sms_fetch_ip,$body);

        $res = json_decode($response);
        if(!is_null($res)){
        $sizeofSMS = sizeof($res->data);
        }
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
                'created_at' => date('Y-m-d h:i:s',$timeStamp),
                'incoming_timestamp' =>$timeStamp
            ]);

        }
        }

//        print_r($res->data[4][1]);

if($sizeofSMS<=0){
    return response()->json(['data'=>NULL,'message'=>'No_sms_retrieved','status'=>'success'],200);
}else{
   return response()->json(['data'=>json_decode($response),'message'=>'Success'],200);
}

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

