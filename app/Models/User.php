<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Passwords\CanResetPassword;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, CanResetPassword, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'phone_number',
        'otp_code',
        'code_last_used'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'active',
        'remember_token',
        'email_verified_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'code_last_used'
    ];





/**
 * Relationship entity mapping for SmsModel
 * @param NULL
 * @return Illuminate\Database\Eloquent\Relations\ HasMany::class
 *
*/
public function SmsModel(): HasMany{
    return $this->hasMany(\App\Models\SmsModel::class);
}


/**
 * Relationship entity mapping for SimModuleModel
 * @param NULL
 * @return Illuminate\Database\Eloquent\Relations\ HasMany::class
 *
*/
//creating an entity relationship between sim module and the sms module
public function SimModule(): HasMany{

    return $this->hasMany(\App\Models\SimModule::class);

}


}
