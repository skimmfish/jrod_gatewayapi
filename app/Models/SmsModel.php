<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [

        'sim_number_sent_to',
        '_msg',
        'msg_type', //outgoing or incoming
        'msg_sender_no',
        'msg_activity_state',
        'active_state',
        'transfer_status'
    ];

    protected $dates = ['updated_at','deleted_at','created_at'];

    protected $cast = ['deleted_at'];


    //creating an entity relationship between sim module and the sms module
    public function SimModule(): BelongsTo{

        return $this->belongsTo(\App\Models\SimModule::class);

    }



}
