<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientEMIInfo extends Model
{
    use HasFactory;

    protected $fillable=[
        'plot_sale_id',
        'EMI_Amount',
        'EMI_Date',
        'EMI_Start_at',
    ];
}
