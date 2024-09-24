<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentDGTransaction extends Model
{
    use HasFactory;

    
    protected $fillable=[
        'agent_id',
        'designation',
        'transaction_amount',
        'Payment_Mode',
    ];

}
