<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens; // Import this trait
// use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class AgentRegister extends Model
{
    use HasApiTokens,HasRoles,HasFactory,Notifiable;

    protected $guard_name = 'api';

    public function agentLevel()
    {
        return $this->hasOne(AgentLevels::class, 'agent_id');
    }

    protected $fillable=[
        'fullname',
        'email',
        'password',
        'referral_code',
        'pancard_no',
        'contact_no',
        'address'
    ];
}
