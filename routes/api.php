<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});*/


//for pulling all resources as it relates to the contactmodelcontroller
//this will fetch contact by id
//this will save new contact


//LOGIN AND AUTHENTICATION ROUTES
//This route doesn't require the Host header, only Accept-Language. Accept Headers are needed
Route::post('/login',[\App\Http\Controllers\Auth\UserController::class,'login_to_authenticate'])->name('login_to_authenticate');

//NEW USER - fixed
Route::post('/new-user',[\App\Http\Controllers\Auth\UserController::class,'store_usr']);

//FORGOT PASSWORD
Route::post('/forgot-password',[\App\Http\Controllers\Auth\UserController::class,'verify_usr_email'])->name('forgot_password_ro');

//FORGOT PASSWORD - non-authenticated endpoint but email must be verified first
Route::post('/update-password',[\App\Http\Controllers\Auth\UserController::class,'update_usr_password'])->name('forgot_password_ro');

//confirm OTP code for new password modification for the user
Route::get('/confirm-otp-code',[\App\Http\Controllers\Auth\UserController::class,'confirm_otp_code'])->name('confirm_otp_code');



//====================GROUP OF AUTHENTICATED ROUTES====================
Route::group(['prefix' => 'v1','middleware' => ['auth:sanctum']], function () {

//FETCH ALL CONTACTS
Route::get('/get-all-contacts',[\App\Http\Controllers\ContactModelController::class,'index'])->name('get_all_contact');

//fetch all configuration parameters
Route::get('/get-all-config-params',[\App\Http\Controllers\ConfigModelController::class,'get_config_parameters'])->name('get_config_parameters');


//for getting all sim card details
Route::post('/logout-user',[\App\Http\Controllers\Auth\UserController::class,'logout']);


//GET USER PROFILE - an authenticated route --fixed
Route::get('/get-user-profile/{id}',[\App\Http\Controllers\Auth\UserController::class,'get_user_profile'])->name('get_user_profile');


//for getting all sim card details by assigning the port_number
Route::get('/get-sim-details-by-port-port-number/{port_id}',[\App\Http\Controllers\SimModuleController::class,'show_by_port'])->name('get_sims');


//for getting all sim card details by sim number
Route::get('/get-sims-by-simcard-number/{sim_number}',[\App\Http\Controllers\SimModuleController::class,'show_sim_by_sim_number'])->name('get_sims_by_sim_number');


//UPDATE LOGIN CREDENTIALS
Route::put('/update-user/{id}',[\App\Http\Controllers\Auth\UserController::class,'updatepassword']);

//getting a single contact using the resource mode
Route::get('/get-contact/{id}',[\App\Http\Controllers\ContactModelController::class,'show']);


//update a contact number
Route::put('/update-sim-contact',[\App\Http\Controllers\ContactModelController::class,'update_contact'])->name('update_sim_contact');

//deleting a sim module configuration on app
Route::delete('/delete-sim-module/{id}',[\App\Http\Controllers\SimModuleController::class,'destroy'])->name('delete_sim_module');


//updating a sim module config
Route::put('/update-sim-module/{id}',[\App\Http\Controllers\SimModuleController::class,'update'])->name('update_sim_module');

//for deleting a resource
Route::delete('/delete-contact/{id}',[\App\Http\Controllers\ContactModelController::class,'destroy']);

//save a new contact
Route::post('/save-new-contact',[\App\Http\Controllers\ContactModelController::class,'store']);

//add new sim configuration to the database table - DONE
Route::post('/add-new-sim-configuration',[\App\Http\Controllers\SimModuleController::class,'store']);


//get contact by arbitrary name
Route::get('/get_contact_by_fname/{}',[\App\Http\Controllers\ContactModelController::class,'get_contact_by_fname']);


//to get sim card port state
Route::get('/get-sim-port-state/{port_id}',[\App\Http\Controllers\SimModuleController::class,'get_port_state'])->name('get_sim_port_state');


//to get all the ports and their availablility
//Route::get('/get-all-port-state',[\App\Http\Controllers\SimModuleController::class,'get_all_port_state'])->name('get_all_port_state');


//get sim by port number
Route::get('/get-single-sim-config/{id}',[\App\Http\Controllers\SimModuleController::class,'show']);


//for sending sms to either single number or bulk numbers
Route::post('/send-sms',[\App\Http\Controllers\SmsModelController::class,'post_send_sms'])->name('send_sms_handle');

//getting a contact number by the number of the contact
Route::get('/get-contact-by-number',[\App\Http\Controllers\ContactModelController::class,'get_contact_by_number'])->name('get_contact_by_number');


//get contacts by the sim card it is saved to
Route::get('/get-contact-by-host/{sim_number}',[\App\Http\Controllers\ContactModelController::class,'get_contact_by_sim_number'])->name('get_contact_by_host');


//get contacts by the port number it is saved to
Route::get('/get-contact-by-port-num/{port_number}',[\App\Http\Controllers\ContactModelController::class,'get_contact_by_port_number'])->name('get_contact_by_host');


//this post CALL changes the state of a contact number - 1=active, 2=archived, 3-blocked (2 & 3 are state of inactivity)
Route::put('/change-contact-number-state/{contact_id}',[\App\Http\Controllers\ContactModelController::class,'change_state_of_contact'])->name('change_state_of_contact');


//get sms messages by sim_number
Route::get('/get-sms-messages-by-portnum/{sim_num}',[\App\Http\Controllers\SmsModelController::class,'get_sms_by_sim_num'])->name('get_sms_portnum');


//get sms messages by port_number
Route::get('/get-sms-messages-by-port/{port_num}',[\App\Http\Controllers\SmsModelController::class,'get_sms_by_port_num'])->name('get_sms_port_num');


//this for deleting sms
Route::delete('/delete-sms-resource/{id}',[\App\Http\Controllers\SmsModelController::class,'deleteResource']);


//show a single sms
Route::get('/read-sms/{id}',[\App\Http\Controllers\SmsModelController::class,'show-sms']);


//FETCH ALL SMS FROM MODEM
Route::get('/get-all-sms-on-modem',[\App\Http\Controllers\SmsModelController::class,'get_all_sms'])->name('get_all_sms');


//This route fetches SMS statistics across the port/slots/date duration type specified
Route::get('/get-sms-stats/{port_id}/{slots}/{type}',[\App\Http\Controllers\SmsModelController::class,'get_sms_stat'])->name('get_all_sms_statistics');


//REBOOT MODEM - for rebooting/reseting the modem remotely via app/mobile app
//Route::post('/reboot-modem',[\App\Http\Controllers\SimModuleController::class,'reboot_modem']);

//===============THIS SECTION FOR SENDING SMS & ADMINISTERING SMS=========================
//send sms by sim_at_a_port
Route::post('/send-single-sms',[\App\Http\Controllers\SmsModelController::class,'send_single_sms'])->name('send_single_sms');

//this route sends bulk sms to multiple recipients @ once
Route::post('/send-bulk-sms',[\App\Http\Controllers\SmsModelController::class,'send_bulk_sms'])->name('send_bulk_sms');

//FOR CHANGING THE STATE OF AN SMS
Route::put('/change-sms-state/{sms_id}',[\App\Http\Controllers\SmsModelController::class,'change_sms_state'])->name('change_received_sms_state');

//parse sms sent with base64 encoding
Route::post('/parse-sms',[\App\Http\Controllers\SmsModelController::class,'parse_sms']);

});



