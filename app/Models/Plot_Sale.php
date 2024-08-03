<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plot_Sale extends Model
{
    use HasFactory;
    
    protected $table = 'plot_sales'; 

    public function transactions()
    {
        return $this->hasMany(PlotTransaction::class, 'plot_sale_id');
    }

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
