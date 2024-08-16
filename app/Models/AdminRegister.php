<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class AdminRegister extends Model
{
    use HasFactory,HasRoles,HasApiTokens, Notifiable;
    
    protected $guard_name = 'api';

    protected $fillable=[
        'name',
        'email',
        'password'
    ];

    protected $hidden = [
        'password'
    ];
}
