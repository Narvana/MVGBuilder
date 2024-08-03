<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentDGSale extends Model
{
    use HasFactory;

    protected $fillable=[
        'agent_id',
        'direct',
        'group'
    ];
}
