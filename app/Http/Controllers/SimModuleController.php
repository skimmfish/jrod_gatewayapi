<?php

namespace App\Http\Controllers;

use App\Models\SimModule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SimModuleController extends Controller
{

    protected $ip_port_only,$api_ip_address,$api_ip_address_at,$api_username,$api_password,$ip_only, $modem_reboot_ip,$mergedURL;

    public function __construct()
    {

        $this->ip_port_only = \App\Models\ConfigModel::get_conn_param('api_port_ip')['value'];

        $this->api_username = \App\Models\ConfigModel::get_conn_param('username')['value'];
        $this->api_password = \App\Models\ConfigModel::get_conn_param('password')['value'];
        $this->ip_only = \App\Models\ConfigModel::get_conn_param('ip_only')['value'];
        $this->api_ip_address = \App\Models\ConfigModel::get_conn_param('api_port_ip')['value'].'/goip_send_ussd.html?username='.$this->api_username.'&password='.$this->api_password;
        $this->api_ip_address_at = \App\Models\ConfigModel::get_conn_param('api_port_ip')['value'].'/goip_send_at.html?username='.$this->api_username.'&password='.$this->api_password;

        $this->modem_reboot_ip= \App\Models\ConfigModel::get_conn_param('api_port_ip')['value'].'/goip_send_cmd.html?username='.$this->api_username.'&password='.$this->api_password.'&version=1.1';
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(\App\Models\SimModule::all());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * @queryParam id integer example: 1,2,3
     */
    public function show($id){

        try{

      $sim =  \App\Models\SimModule::where('id',$id)->first();

      return response()->json(['data'=>$sim,'message'=>'success'],200);

     }catch(\Exception $e){
            return response()->json(['data'=>NULL,'error'=>$e->getMessage(),'message'=>'error'],500);
        }
    }

    /**
     * This function reboots the modem remotely
     *
     * @bodyParam NULL
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN
     * @header Host 54.179.122.227:52538

     * @response{
     *   'data'=>\Illuminate\Http\Response,
     *   'status'=>'success' or 'fail', 'Success if the modem is rebooted successfully
     *     'message' =>'Modem rebooted successfully'
     * }
     */
    public function reboot_modem(){

        $this->mergedURL = $this->modem_reboot_ip.'&op=reset&ports=all,*';
        $body = [];
        try{
        $response = \App\Models\ConfigModel::callAPI('POST',$this->mergedURL,$body);
        return response()->json(['data'=>json_decode($response),'status'=>'success','message'=>'Modem rebooted successfully'],200);

        }catch(\Exception $e){
            return response()->json(['data'=>NULL,'message'=>'error','error'=>$e->getMessage()],500);
        }

    }

    /**
     * Creating a new sim module in modem port, saved to the database for state retrieval during app loading.
     *
     * @queryParam  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN

     * @bodyParam sim_number string required Example: +16302901990
     * @bodyParam sim_port_number Integer required unique: Example: 1,2
     * @bodyParam current_port_state Integer required Default: 1
     *
     * @request{
     *
     *   'sim_number'=> string example: +123490332909,
     *   'sim_port_number' => integer example 1-8,
     *   'current_port_state'=> integer example: 1/0, //by default, its assumed to be inactive until the status is fetched
     *
     *
     *
     * }
     *
     * @response{
     * 'data'=>\Illuminate\Http\Response $newSim object, this represents the sim module that was just created
     * 'message'=>'Sim card module set and saved successfully
     *
     *
     * }
     */

    public function store(Request $request)
    {
        $request->validate($request->all());
        //to add a new sim card to the model
        try{
        $newSim = \App\Models\SimModule::create([
            'sim_number'=>$request->sim_number,
            'sim_port_number' => $request->sim_port_number,
            'current_port_state'=>$request->current_port_state, //by default, its assumed to be inactive until the status is fetched
            'created_at'=>date('Y-m-d h:i:s',time()),
            'updated_at'=>date('Y-m-d h:i:s',time())
        ]);

        return response()->json(['data'=>$newSim,'message'=>'success', 'status'=>'Sim card module set and saved successfully'],200);

        }catch(\Exception $e){
            return response()->json(['data'=>NULL,'message'=>'error','error'=>$e->getMessage()],500);
        }
    }


    /**
     * This function gets all the port state and the available ones
     * @bodyParam NULL
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN
     * @header Host 54.179.122.227:52538

     * @response{
     * 'data': json_decode(\Illuminate\Http\Response $response),
     * 'message': 'success' or 'error'
     * }
     */
    public function get_all_port_state(){

        $url = $this->ip_port_only.'/goip_send_ussd.html?username='.$this->api_username.'&password='.$this->api_password;
        $body = [];


    try{

        $response = \App\Models\ConfigModel::callAPI('GET',$url,$body);

//        \Log::info($response);

        return response()->json(['data'=>json_decode($response),'message'=>'success'],200);

    }catch(Exception $e){
        return response()->json(['data'=>NULL,'message'=>'error','error'=>$e->getMessage()],200);
    }

}

    /**
     * This function retrieves the port state of a modem port that has a sim on it, if the reason token is 'OK', modify the state of the sim
     * by calling API /update-sim-module endpoint
     *
     * @queryParam port_id Integer Example: 1,2,3-8
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN
     *
     * @response{
     * 'data':json_decode(\Illuminate\Http\Response $response),
     * 'id': sim_module primary key reference
     * 'message':'success' or 'error'
     * }
     */

     public function get_port_state($port_id){
        $state = false;

        $body = [];

        try{

    $this->api_ip_address_at = $this->api_ip_address_at.'&port='.$port_id.'&at=ati';


  $response = \App\Models\ConfigModel::callAPI('GET',$this->api_ip_address_at,$body);


        /*$datar = Http::withHeaders([

            'Content-Type' => 'application/json',
            'Host' => '54.179.122.227:52538',
            'Connection' => 'close',*/
/*            //'Accept' => "*"

            ])->get($this->api_ip_address_at);
*/

      $getSim =  \App\Models\SimModule::where('sim_port_number',$port_id)->first();

      $data = json_decode($response);

            \Log::info($response);

  if($data->reason=='OK'){
    \DB::update("UPDATE sim_modules SET current_port_state=? WHERE sim_port_number=?",[true,$port_id]);
    }

return response()->json(['data'=>json_decode($response),'id'=>$getSim->id,'message'=>'success'],200);

}catch(\Exception $e){

    return response()->json(['data'=>NULL,'message'=>'error','error'=>$e->getMessage()],500);

}
}



/**
 * This function retrieves all the sms sent/received by a sim
 * @bodyParam $sim_number String, Example: +1629029019
 *
 * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN
     *
 * @response{
 * 'data': $resultSet[],
 * 'message':'success' or 'error
 * }
 *
 */
 public function get_all_sms_for_sim_by_number($sim_number){
 try{
   $resultSet = \App\Models\SmsModel::where(['msg_sender_no'=>$sim_number])->orWhere(['sim_number_sent_to'=>$sim_number])->orderBy('created_at','DESC')->get();

   if(sizeof($resultSet)>0){
    return response()->json(['data'=>$resultSet,'message'=>'success'],200);
   }else{

    return response()->json(['data'=>NULL,'message'=>'Empty result set'],200);

   }
    }catch(\Exception $e){

        return response()->json(['data'=>null,'message'=>'error','error'=>$e->getMessage()],404);
    }

}


/**
     * This displays the entire information about a sim card fetching by the port number.
     *
     * @bodyParam  $sim_number Integer Example: +1520109209
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN
     *
     * @response{
     * 'data': \Illuminate\Http\Response $simData,
     * 'message': 'success'
     * }
     */

public function show_sim_by_sim_number($sim_number){
       // $id = $request->id;
        //getting sim data by id
        try{

            $simData = \App\Models\SimModule::where(['sim_number'=>$sim_number])->first();

            return response()->json(['data'=>$simData,'message'=>'success'],200);

        }catch(\Exception $e){

            return response()->json(['data'=>NULL,'error'=>$e->getMessage()],404);

        }

}

/**
     * This displays the entire information about a sim card fetching by the port number.
     *
     * @bodyParam $port_id Integer Example: 1
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN
     *
     * @response{
     * 'data': \Illuminate\Http\Response $simData,
     * 'message': 'success'
     * }
     */
    public function show_by_port($port_id)
    {
       // $id = $request->id;
        //getting sim data by id
        try{

        $simData = \App\Models\SimModule::where(['sim_port_number'=>$port_id])->first();

        return response()->json(['data'=>json_decode($simData),'message'=>'success'],200);

    }catch(\Exception $e){

        return response()->json(['data'=>NULL,'error'=>$e->getMessage()],404);

    }
    }


    /**this function retrieves the sim card number using the port id
     * @param port_id Integer
     *
    */
    public function get_simnumber_by_port_id($port_id){

        try{
        $simNo = null;

        $simDet =  \App\Models\SimModule::where(['sim_port_number'=>$port_id])->first();

        if(!is_null($simDet)){
            $simNo = $simDet->sim_number;
        }else{
            return NULL;
        }

        }catch(\Exception $e){
            return $e->getMessage();
        }

    return $simNo;
    }


    /**
     * Update the sim module or sim card information
     *
     * @queryParam id Integer Example: 1,2,3,4

     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN
     *
     * @bodyParam sim_number string example: +12449080909
     * @bodyParam sim_port_number string example: 1-8
     * @bodyParam current_port_state integer example: 1 or 0
     * $bodyParam sim_name string example: sim_2 or jrodil_2
     *
     * @request{
     *  'id',
     *  'sim_number,
     *  'sim_port_number',
     *  'current_port_state,
     * 'sim_name',
     *  'updated_date'
     * }
     *
     * @response{
     *
     * 'data':\Illuminate\Http\Response $simModule,
     * 'message': 'success' or 'fail'
     *
     * }
     */

    public function update(Request $request, SimModule $simMod, $id){

        try{
        $simModule = $simMod->findOrFail($id);
        $simModule->sim_name = $request->sim_name;
        $simModule->sim_number = $request->sim_number;
        $simModule->sim_port_number = $request->sim_port_number;
        $simModule->current_port_state = 1;
        $simModule->updated_at = date('Y-m-d h:i:s',time());
        $simModule->save();

        return response()->json([
        'data'=>$simModule,
        'message'=>'success'],200);

        }catch(\Exception $e){

        return response()->json(['data'=>NULL,'message'=>'error','error'=>$e->getMessage()],200);

    }
    }

    /**
     * This function gets all sim card info
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN

     *
     */
    public function get_all_sims_info(){

        try{

            $allSims = \App\Models\SimModule::all();
if(sizeof($allSims)>0){
     return response()->json(['data'=>$allSims,'message'=>'success'],200);
}else{
    return response()->json(['data'=>NULL,'message'=>'success'],200);
}


        }catch(\Exception $e){
            return response()->json(['data'=>NULL,'message'=>'error','error'=>$e->getMessage()],400);
        }

    }


    /**
     * Remove the specified resource from storage.
     *
     * @bodyparam  $simModule \App\Models\SimModule  a SimModule object
     * @bodyParam $id Integer
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN
     *
     *
     * @response{
     * 'data': \Illuminate\Http\Response,
     * 'status':'success' or 'error'
     * }
     */
    public function destroy(SimModule $simModule,$id)
    {
        try{
        $response = $simModule->findOrFail($id)->forceDelete();
          return response()->json(['data'=>$response,'status'=>'success'],200);
        }catch(\Exception $e){
            return response()->json(['data'=>null,'message'=>'error','error'=>$e->getMessage()],400);
        }

    }

/**
 * send_single_sms for sending sms to only one recipient per time
 */
    public function send_single_sms(){

        echo "Route found";

    }
}
