<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('errors.index');
})->name('home_base');

//for setting up event stream while loop
Route::get('/run-event-stream/{authorization_code}',function($authorization_code){

   $authorCode = \App\Models\ConfigModel::get_conn_param('authorization_code');
    $smsControl = new \App\Http\Controllers\SmsModelController;

   if($authorCode['value'] == $authorization_code){
    //call the event stream
  $res = $smsControl->fetch_sms_from_gateway();

  $smsControl->gtstream();
//  print_r(json_decode($res));

  //echo $res->_msg;
}


})->name('event_stream');

//Route::get('')

require __DIR__.'/auth.php';
