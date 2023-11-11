<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'api_ip_address',
        'api_port',
        'api_username',
        'api_password'
    ];

    protected $hidden = ['api_ip_address','api_username','api_password','api_port'];


    //this function returns the connection parameter value
    public static function get_conn_param($key_name){

    $config = ConfigModel::where(['key_name'=>$key_name,'key_state'=>true])->first();

    return [
            'value'=>$config->key_value,
            'state'=>$config->key_state
        ];
    }


//calling API methods
public static function callAPI($method, $url, $data){

    $ip_only =  \App\Models\ConfigModel::get_conn_param('ip_only')['value'];

    $curl = curl_init();
    switch ($method){
       case "POST":
          curl_setopt($curl, CURLOPT_POST, 1);
          if ($data)
             curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
          break;
       case "PUT":
          curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
          if ($data)
             curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
          break;

          case "GET":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
            if ($data)
               curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
       default:
          if ($data)
             $url = sprintf("%s?%s", $url, http_build_query($data));
    }
    // OPTIONS:
    curl_setopt($curl, CURLOPT_URL, $url);

    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    //'x-api-key: '.\App\Models\CryptoAPIManager::get_value('nowapi_key'),
    //'Content-Type: application/json'

    'Connection : close',
    'Accept-Language : zh-CN',
    'Host : 54.179.122.227',
    'Content-Type : application/json;charset=utf-8',
    'Accept: */*'
    ));

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    // EXECUTE:
    $result = curl_exec($curl);
    if(!$result){die("Connection Failure");}
    curl_close($curl);
    return $result;
 }


}
