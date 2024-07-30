<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plot_Sale extends Model
{
    use HasFactory;
    
    protected $table = 'plot_sales'; 

    protected $fillable=[
        'plot_id',
        'client_id',
        'agent_id',
        'initial_amount',
        'totalAmount',
        'plot_status',
        'plot_value',
    ];
}
