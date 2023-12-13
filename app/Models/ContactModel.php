<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contact_no',
        'contact_alt_number',
        'contact_fname',
        'contact_lname',
        'contact_state',
        'sim_contact_saved_to',
        'port_number'
    ];

    protected $dates = ['created_at','updated_at','deleted_at'];

    protected $cast = ['deleted_at'];



    //creation entity relationship between the sim module and the contacts
    //a contact belongs to a sim card
    public function SimModule(): belongsTo{
        return $this->belongsTo(\App\Models\SimModule::class);
    }
}
