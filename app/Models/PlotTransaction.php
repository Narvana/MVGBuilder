<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlotTransaction extends Model
{
    use HasFactory;
    protected $table = 'plot_transactions'; 

    protected $fillable=[
        'transaction_id',
        'amount',
        'plot_sale_id',
        'payment_method'
    ];
}
