function fetchServersList(brokerName){
    if(brokerName){
    $.ajax({
    type: 'GET',
    url: "{{ route('admin.dashboard.get_server_list') }}",
    //url: "http://localhost:8000/server_list/",
    data: {
    broker_name: brokerName,
    },
    success: function (response) {
     // We get the element having id of display_info and put the response inside it
     $( '#server_list' ).html(response);
    }
    });
   }else
   {
    $( '#server_list' ).html("<small class='text-danger'>Please check your selection</small>");
   }
} //end of fetchServersList() function

/*
//<!--fetchServersList()-->

function fetchServersList(brokerName){
    if(brokerName){
    $.ajax({
    type: 'GET',
    url: "{{ route('admin.dashboard.get_server_list') }}",
    data: {
    broker_name: brokerName,
    },
    success: function (response) {
     // We get the element having id of display_info and put the response inside it
     $( '#server_list' ).html(response);
    }
    });
   }else
   {
    $( '#server_list' ).html("<small class='text-danger'>Please check your selection</small>");
   }
} //end of fetchServersList() function


//fetchRecordsAll() fetches records based on a start date and end date
function fetchRecordsAll(start,end){

}


//fetchRecordsByPeriod, this fetches records based on date_period only
function fetchRecordsByPeriod(date_period){

}
*/
