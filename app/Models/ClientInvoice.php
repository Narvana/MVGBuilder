<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientInvoice extends Model
{
    use HasFactory;

    protected $fillable=[
        'Invoice_no',
        'Client_name',
        'Client_contact',
        'Client_address',
        'Site_Name',
        'Plot_No',
        'Plot_Area',
        'Transaction_id',
        'Amount',
        'Transaction_date',
        'Agent_name',
        'Payment_Method',
        'Payment_Detail'
    ];

}
