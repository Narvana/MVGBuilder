<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentIncome extends Model
{
    use HasFactory;
    
    protected $fillable =[
        'plot_sale_id',
        'total_income',
        'tds_deduction',
        'final_income',
        'pancard_status',
        'transaction_status',
        'final_agent',
    ];
}
