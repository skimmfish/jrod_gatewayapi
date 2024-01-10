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
     * @bodyParam String contact_no Example: +12901009100
     * @bodyparam String contact_fname Example: John
     * @bodyParam String contact_lname Example: Reid
     * @bodyParam String sim_contact_saved_to Example: +12410209102 - //you can make this a drop-down selection to pick the sim number from the list of all sim cards' numbers stored initially instead of making the user enter it manually
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
            'contact_no'=>['required','string','max:14','min:11'],
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
     * @queryParam Integer id Example: 1
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

     return response()->json(['data'=>json_encode($response),'message'=>'success'],200);

        }catch(\Exception $e){
            return response()->json(['data'=>NULL,'message'=>'error','error'=>$e->getMessage()],404);
        }

    }



    /**
     * Display only a category of contacts
     *
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/octet-stream
     * @header Authorization Bearer AUTH_TOKEN
     *
     * @queryParam Integer type_id Example: 1 or 2 or 3

     * @response{
     *  'data': [],
     *  'message': 'success' or 'error
     *
     * }
     */

public function show_by_type($type_id){

    try{

        $response =  \App\Models\ContactModel::where(['contact_state'=>$type_id])->get();

        return response()->json(['data'=>json_encode($response),'message'=>'all active numbers fetched','status'=>'success'],200);

           }catch(\Exception $e){
               return response()->json(['data'=>NULL,'message'=>'error','error'=>$e->getMessage()],404);
           }

}

    /**
     * Show Contact by Phone Number, this retrieves all the isntances of the phone number perhaps its saved on more than one sim
     * @queryParam String contact_number Example: +1289309200019
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

return response()->json(['data'=>json_encode($contact),'message'=>'success'],200);

    }catch(\Exception $e){
        return response()->json(['data'=>NULL,'error'=>$e->getMessage()],404);
    }
}

/**
 * This function retrieves a contact by searching with the first name
 * @queryParam String f_name Example: john
 *
 * @header Connection keep-alive
 * @header Accept * / *
 * @header Content-Type application/octet-stream
 * @header Authorization Bearer AUTH_TOKEN

 * @response{
 * 'data': \Illuminate\Http\Response $response $result,
 * 'message': 'success' or 'error'
 * }
 */
public function get_contact_by_fname($f_name){

    try{
   $result = \DB::SELECT("SELECT *from contact_models WHERE contact_fname LIKE ? ORDER BY ? DESC ",[$f_name,'created_at']);

   //$result = \App\Models\ContactModel::where(['contact_fname'=>$f_name])->orderBy('created_at','DESC')->get();

   if(sizeof($result)>0){
   return response()->json(['data'=>json_encode($result),'message'=>'success'],200);
   }else{
    return response()->json(['data'=>null,'message'=>'no_record_found'],404);
   }
    }catch(\Exception $e){
        return response()->json(['data'=>NULL,'message'=>'error','error'=>$e->getMessage()],404);
    }

}


/**
 * this function gets all contacts on a particular sim card by its number
 * @queryParam String sim_number
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
    return response()->json(['data'=>json_encode($allContacts),'message'=>'success'],200);
}catch(\Exception $e){

    return response()->json(['data'=>NULL,'message'=>'Error'],404);
}

}


/**
 * this function gets all contacts on a particular sim card by its number
 * @queryParam String port_number
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

     * @queryParam Integer contact_id Example: 1,2,3,4
     * @bodyParam integer contact_state example:  1 for default (if active),2 for Archive,3 for Blacklist etc.

     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN

     * @request: {
     *   'contact_state' : contact_state
     * }

    * @response{
    * 'data': \Illuminate\Http\Response $response $res,
    * 'message': 'success' or 'error'
    * }

     *
     */

    public function change_state_of_contact(Request $request,$contact_id){

    try{
        $rules = [
            'contact_state'=>['required','integer']
        ];

        $request->validate($rules);

        $contact = \App\Models\ContactModel::find($contact_id);
        $contact->contact_state =  $request->contact_state;

        $res = $contact->save();

        return response()->json(['data'=>$res,'message'=>'Success'],200);

    }catch(\Exception $e){
        return response()->json(['data'=>NULL,'message'=>'error','exception'=>$e->getMessage()],404);
    }

}



    /**
     * This function alters the state of a sim contact

     * @queryParam Integer contact_id Example: 1,2,3,4
     * @bodyParam integer contact_state example:  1 for default (if active),2 for Archive,3 for Blacklist etc.

     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN
    *
    *  @request:{
    *   'contact_ids':[contact ids separated by ',']
    * }
    * @response{
    * 'data': \Illuminate\Http\Response $response $res,
    * 'message': 'success' or 'error'
    * }

     *
     */

public function change_state_of_contact_multiple(Request $request){

    try{

        $rules = [
            'contact_state'=>['required','integer']
        ];

        //validating the rules
        $request->validate($rules);

        $contact_ids = ($request->contact_ids);

    foreach($contact_ids as $i){
    $contact = \App\Models\ContactModel::findOrFail($i);
    $contact->contact_state =  $request->contact_state;
    $res = $contact->save();
    }

    return response()->json(['status'=>'success','message'=>'All contacts state modified successfully'],200);


}catch(\Exception $e){

    return response()->json(['data'=>NULL,'message'=>'error','exception'=>$e->getMessage()],404);

}


}
/**
     * this function fetches all contacts based on the supplied state parameter
     * The contact_state param determines if the contacts you would retrieve is active (which is default), archived or blacklisted contacts
     * @queryParam Integer contact_state Example: 1 = for all active contacts, 2 = for all archived contacts, 3 = for all blacklisted contacts

     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN

     */

     public function fetch_contacts_by_state($contact_state){

    try{

    $allSuch =  \App\Models\ContactModel::where('contact_state',$contact_state)->get();

    if(sizeof($allSuch)>0){

        return response()->json(['data'=>$allSuch,'status'=>true,'message'=>'success'],200);
    }else{

        return response()->json(['data'=>NULL,'status'=>false,'message'=>'No such contacts found'],404);

        }
        }catch(\Exception $e){

            return response()->json(['data'=>NULL,'error'=>$e->getMessage()],500);
        }

}


   /**
     * this function fetches all archived contacts
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN
     */

    public function fetch_archived_contacts(){

        try{

        $allSuch =  \App\Models\ContactModel::where('contact_state',2)->get();

        if(sizeof($allSuch)>0){

            return response()->json(['data'=>$allSuch,'status'=>true,'message'=>'success'],200);
        }else{

            return response()->json(['data'=>NULL,'status'=>false,'message'=>'No such contacts found'],404);

            }
            }catch(\Exception $e){

                return response()->json(['data'=>NULL,'error'=>$e->getMessage()],500);
            }


}
    /**
     * this function fetches all blacklisted contacts
     * @header Connection keep-alive
     * @header Accept * / *
     * @header Content-Type application/json;utf-8
     * @header Authorization Bearer AUTH_TOKEN

     */

     public function fetch_blacklisted_contacts(){

        try{

        $allSuch =  \App\Models\ContactModel::where('contact_state',3)->get();

        if(sizeof($allSuch)>0){

            return response()->json(['data'=>$allSuch,'status'=>true,'message'=>'success'],200);
        }else{

            return response()->json(['data'=>NULL,'status'=>false,'message'=>'No such contacts found'],404);

            }
            }catch(\Exception $e){

                return response()->json(['data'=>NULL,'error'=>$e->getMessage()],500);
            }

    }



/**
     * Removes a contact from the contact database table
     *
     * @queryParam Integer id Example: 1,2,3,4
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


   /**
    * This function deletes all contacts in the phone book
    * @header Connection keep-alive
    * @header Accept * / *
    * @header Content-Type application/json;utf-8
    * @header Authorization Bearer AUTH_TOKEN
    */

   public function delete_all_contact(){

try{

$res = \DB::delete("DELETE FROM contact_models");

return response()->json(['data'=>'all_contacts_deleted_successfully','status'=>true],200);

}catch(\Exception $e){
    return response()->json(['data'=>'error','status'=>false,'error'=>$e->getMessage()],500);
}
   }
}
