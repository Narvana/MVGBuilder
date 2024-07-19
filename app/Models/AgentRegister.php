<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens; // Import this trait
// use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AgentRegister extends Model
{
    use HasApiTokens,HasFactory,Notifiable;
    
    protected $fillable=[
        'fullname',
        'email',
        'password'
    ];
}
