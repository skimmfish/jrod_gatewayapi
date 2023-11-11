<?php

namespace App\Http\Controllers;

use App\Models\ConfigModel;
use Illuminate\Http\Request;

class ConfigModelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     * @bodyParam NULL
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN

     * @response{
     * "data": $config information payload
     * "message": 'success' or 'error'
     * }
     */
    public function get_config_parameters()
    {
        try{
        $config_params = \App\Models\ConfigModel::all();
        return response()->json(['data'=>$config_params,'message'=>'success'],200);
        }catch(\Exception $e){
            return response()->json(['data'=>NULL,'message'=>'failed','error'=>$e->getMessage()],403);

        }
    }


    /**
     * Update the specified resource in storage.
     *  Each key_name and key_value pair is to be saved individually to avoid conflict of interest with key-name/value mapping
     * @queryParam  \Illuminate\Http\Request  $request
     * @queryParam  $id Integer this is the primary key value of each key-name/value mapping Example: 1,
     *
     * @bodyParam $key_name String Example: 'ip_address'
     * @bodyParam $key_value Any Example: string/integer/boolean

     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN

     * @response{
     *  "data": \Illuminate\Http\Response $response,
     *  "message":'success' or 'error'
     * }
     */
    public function update(Request $request,$id)
    {
    try{
       $response = \DB::update("UPDATE config_models SET key_value=? WHERE key_name=? AND id=?",[$request->key_value,$request->key_name,$id]);
        return response()->json(['data'=>$response,'message'=>'success'],200);

    }catch(\Exception $e){
            return response()->json(['message'=>'fail','error'=>$e->getMessage()],500);
        }

    }

    /**
     * Remove the specified resource having key-value mapping from storage.
     *
     * @queryParam $id Integer required Example: 1
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN

     * @response{
     * "message": 'success' or 'error'
     * }
     *
     */
    public function destroy($id)
    {
        try{
        $keyValue = \App\Models\ConfigModel::findOrFail($id);

        $RESPONSE = $keyValue->forceDelete($id);

        return response()->json(['message'=>'success'],200);


        }catch(\Exception $e){
            return response()->json(['data'=>NULL,'error'=>$e->getMessage()],500);
        }
    }
}
