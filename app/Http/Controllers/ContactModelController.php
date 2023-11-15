<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactsRequest;
use App\Models\ContactModel;
use App\Models\SimModule;
use Illuminate\Http\Request;

class ContactModelController extends Controller
{

    /**
     * Display a listing of the resource.
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN
     *
     * @return \Illuminate\Http\Response
     *
     * @request{
     *
     * }
     * @response{
     * 'data': []
     * 'message': string
     * }
     */
    public function index()
    {

    try{

    $allContact = \App\Models\ContactModel::all();


    return response()->json(['data'=>$allContact,'message'=>'success'],200);

}catch(\Exception $e){

        return response()->json(['data'=>NULL,'message'=>'error','error'=>$e->getMessage()],500);
}
    }

    /**
     * Store a newly created contact
     *
     * @bodyParam contact_no String Example: +12901009100
     * @bodyparam contact_fname String Example: John
     * @bodyParam contact_lname String Example: Reid
     * @bodyParam sim_contact_saved_to String Example: +12410209102 - //you can make this a drop-down selection to pick the sim number from the list of all sim cards' numbers stored initially instead of making the user enter it manually
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN

     * @request{
     *    'contact_no'=> string,
     *    'contact_fname'=> string,
     *    'contact_lname'=> string,
     *    'sim_contact_saved_to'=> string, //you can make this a drop-down selection to pick the sim number from the list of all sim cards' numbers stored initially instead of making the user enter it manually
     *    'contact_state' => integer Example 1/0,
     *    'port_number' => integer Example 1-8  //this is going to be the sim port of the phone number selected above
     *    }
     *
     *  @response{
     * 'data': \Illuminate\Http\Response $newcontact,
     * 'message':'success' or 'error',
     *
     * }
     */


    public function store(Request $request)
    {
        $port_number = null;

        try{

            $rules =  [
            'contact_no'=>['required','string','max:14','min:14'],
            'contact_fname'=>['required','string','max:50','min:3'],
            'contact_lname'=>['required','string','max:50','min:3'],
            'sim_contact_saved_to' => ['required','string']
        ];

            //running validation for contacts to be stored
            $error = $request->validate($rules);

            //port number fetch
            $port_number_id = SimModule::where(['sim_number'=>$request->sim_contact_saved_to])->first();

            if(!is_null($port_number_id)){
                $port_number = $port_number_id->sim_port_number;
            }

            //this saves a new contact to the DB, fetched to the mobile app via sync
            $newcontact = \App\Models\ContactModel::create([

            'contact_no'=>$request->contact_no,
            'contact_fname'=>$request->contact_fname,
            'contact_lname'=>$request->contact_lname,
            'sim_contact_saved_to'=>$request->sim_contact_saved_to,
            'contact_state'=>1,
            'port_number'=> $port_number, //sim_port_number as at the time the contact is being saved
            'created_at'=> date('Y-m-d h:i:s',time()),
            'updated_at'=> date('Y-m-d h:i:s',time()),

        ]);

        //\DB::update("UPDATE contact_models SET sim_contact_saved_to=? WHERE id=?",[$request->]);

        return response()->json(['data'=>$newcontact,'message'=>'success'],200);

    }catch(\Exception $e){
        return response()->json(['data'=>NULL,'message'=>'failed','error'=>$e->getMessage()],500);
    }
    }

    /**
     * Display a single contact.
     *
     * @queryParam Integer $id Example: 1
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN

     * @response{
     *  'data': [],
     *  'message': 'success' or 'error
     *
     * }
     */
    public function show($id)
    {
    try{

     $response =  \App\Models\ContactModel::findOrFail($id);

     return response()->json(['data'=>$response,'message'=>'success'],200);

        }catch(\Exception $e){
            return response()->json(['data'=>NULL,'message'=>'error','error'=>$e->getMessage()],404);
        }
    }


    /**
     * Show Contact by Phone Number, this retrieves all the isntances of the phone number perhaps its saved on more than one sim
     * @queryParam $contact_number String Example: +1289309200019
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN

     * @response{
     * 'data': []
     * 'message':'success'
     * }
     */

public function get_contact_by_number($contact_number){
try{

    $contact = \App\Models\ContactModel::Where(['sim_number'=>$contact_number])->first();

return response()->json(['data'=>$contact,'message'=>'success'],200);

    }catch(\Exception $e){
        return response()->json(['data'=>NULL,'error'=>$e->getMessage()],404);
    }
}

/**
 * This function retrieves a contact by searching with the first name
 * @queryParam $f_name String Example: john
 *
 * @response{
 * 'data': \Illuminate\Http\Response $response $result,
 * 'message': 'success' or 'error'
 * }
 */
public function get_contact_by_fname($f_name){

    try{
   $result = \DB::SELECT("SELECT from contact_models WHERE contact_fname LIKE ? ORDER BY ? DESC",[$f_name,'ASC']);

   return response()->json(['data'=>$result,'message'=>'success'],200);

    }catch(\Exception $e){
        return response()->json(['data'=>NULL,'message'=>'error','error'=>$e->getMessage()],404);
    }

}


/**
 * this function gets all contacts on a particular sim card by its number
 * @queryParam $sim_number String
 *
   * @header Connection keep-alive
   * @header Accept * / *
   * @header Content-Type application/octet-stream
   * @header Authorization Bearer AUTH_TOKEN

 * @response{
 * 'data': \Illuminate\Http\Response $response $allContacts,
 * 'message': 'success' or 'error'
 * }

 */
public function get_contact_by_sim_number($sim_number){

try{
    $allContacts = \App\Models\ContactModel::where('sim_contact_saved_to ',$sim_number)->get();
    return response()->json(['data'=>$allContacts,'message'=>'success'],200);
}catch(\Exception $e){

    return response()->json(['data'=>NULL,'message'=>'Error'],404);
}

}


/**
 * this function gets all contacts on a particular sim card by its number
 * @queryParam $port_number String
 *
   * @header Connection keep-alive
   * @header Accept * / *
   * @header Content-Type application/octet-stream
   * @header Authorization Bearer AUTH_TOKEN

 * @response{
 * 'data': \Illuminate\Http\Response $response $allContacts,
 * 'message': 'success' or 'error'
 * }

 */
public function get_contact_by_port_number($port_number){

    try{
        $allContacts = \App\Models\ContactModel::where('port_number',$port_number)->get();
        return response()->json(['data'=>$allContacts,'message'=>'success'],200);
    }catch(\Exception $e){

        return response()->json(['data'=>NULL,'message'=>'Error'],404);
    }

    }



    /**
     * This function modifies a contact
     *
     * @queryParam  \Illuminate\Http\Request  $request
     * @queryParam Integer $id
     *
     * @bodyParam $contact_no String Example: +102901990190
     * @bodyParam  $contact_alt_number String Example: +1290091800029
     * @bodyParam  $contact_fname String Example: John
     * @bodyParam  $contact_lname String Example: Sean
     * @bodyParam  $sim_contact_saved_to String Example: +12390930944
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN

     *  @request{

     *  'contact_no'  => string
     *  'contact_alt_number'  => string,
     *  'contact_fname' => string,
     *  'contact_lname' => string,
     *  'sim_contact_saved_to'  => string

     *  }
     *
     * @response{
     * 'data': [],
     * 'message': string
     * }
     *
     *
     */
    public function update_contact(Request $request, $id){

        try{

        $contactModel = \App\Models\ContactModel::findOrFail($id);
        $contactModel->contact_no  = $request->contact_no;
        $contactModel->contact_alt_number  = $request->contact_alt_number;
        $contactModel->contact_fname = $request->contact_fname ;
        $contactModel->contact_lname = $request->contact_lname;
        $contactModel->sim_contact_saved_to  = $request->sim_contact_saved_to;
        $contactModel->updated_at = date('Y-m-d h:i:s',time());

        //saving
        $response = $contactModel->save();
        return response()->json(['data'=>$response,'message'=>'success'],200);

        }catch(\Exception $e){

            return response()->json(['message'=>'error','error'=>$e->getMessage()],500);
        }
    }


    /**
     * This function alters the state of a sim contact

     * @queryParam Request $request
     * @queryParam $contact_id Integer Example: 1,2,3 etc

     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN

     * @bodyParam $contact_state Example: true/false
     * @response{
    * 'data': \Illuminate\Http\Response $response $res,
    * 'message': 'success' or 'error'
    * }

     *
     */
    public function change_state_of_contact(Request $request,$contact_id){

    try{
        $contact = \App\Models\ContactModel::find($contact_id);
        $contact->contact_state =  $request->contact_state;

        $res = $contact->save();

        return response()->json(['data'=>$res,'message'=>'Success'],200);
    }catch(\Exception $e){
        return response()->json(['data'=>NULL,'message'=>'error','exception'=>$e->getMessage()],404);
    }

}
    /**
     * Update the specified resource in storage.
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN

     * @queryParam  \Illuminate\Http\Request  $request
     * @queryParam  \App\Models\ContactModel  $contactModel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ContactModel $contactModel)
    {
        //
    }

    /**
     * Removes a contact from the contact database table
     *
     * @bodyParam  NULL
     * @queryParam $id Integer Example: 1,2,3,4
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN
     * @response{
     * 'message':'success' or 'error'
     * }
     */
    public function destroy($id)
    {
  try{

        $contactModel = new ContactModel;

        $res =   $contactModel->find($id)->forceDelete();

        return response()->json([
        'message'=>'success'
        ],200);

  }catch(\Exception $e){
    return response()->json(['data'=>NULL,'error'=>$e->getMessage(),'message'=>'error'],500);
  }
   }
}
