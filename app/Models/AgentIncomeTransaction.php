<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentIncomeTransaction extends Model
{
    use HasFactory;
    protected $fillable=[
        'agent_id',
        'income_type',
        'transaction_status',
        'transaction_amount',
        'plot_sale_id',
        'Payment_Mode'
    ];
}
