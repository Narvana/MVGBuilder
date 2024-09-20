<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentBonanza extends Model
{
    use HasFactory;
    protected $fillable=[
        'agent_id',
        'Area_Sold',
        'Bonanza_Place',
        'Bonanza_Days',
        'Bonanza_Received',
    ];
}
