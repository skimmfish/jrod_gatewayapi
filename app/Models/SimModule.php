<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

class SimModule extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'sim_number',
        'sim_port_number',
        'current_port_state',
    ];

    protected $dates = ['deleted_at','updated_at','created_at'];

    protected $cast = ['deleted_at'];

    //creating an entity between a simModule objecct and contactModel
    public function ContactModel(): HasOneOrMany{
        return $this->HasOneOrMany(\App\Models\ContactModel::class);
    }


    //creating entity relationship between SmsModel module and SimModule sim card
    public function SmsModel(): HasOneOrMany{
    return $this->HasOneOrMany(\App\Models\SmsModel::class);
    }
}
