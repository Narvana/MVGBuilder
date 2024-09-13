<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentReward extends Model
{
    use HasFactory;

    protected $fillable=[
        'agent_id',
        'Direct',
        'Group',
        'Reward_Achieved',
        'Reward_Received',
        'Next_Reward',
        'Area_Sold'
    ];

}
