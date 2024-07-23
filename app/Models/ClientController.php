<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientController extends Model
{
    use HasFactory;
    protected $fillable=[
        'client_name',
        'client_contact',
        'client_address',
        'client_city',
        'client_state',
        'plot_id',
        'agent_id'
    ];
}
