<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plot extends Model
{
    use HasFactory;
    protected $fillable=[
        'site_id',
        'plot_No',
        'plot_type',
        'plot_area',
        'price_from',
        'price_to',
        'price_status'
    ];
}
