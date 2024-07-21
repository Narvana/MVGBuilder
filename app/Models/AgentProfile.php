<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentProfile extends Model
{
    use HasFactory;
    protected $fillable=[
        'agent_id',
        'designation',
        'description',
        'contact_no',
        'address'
    ];
}
