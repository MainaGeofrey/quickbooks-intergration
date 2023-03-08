<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DB_Customer extends Model
{
    use HasFactory;


    protected $table  = 'customers';
    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'client_id',
        'account_name',
        'mobile_number',
        'email',
        'company_name',
        'balance',
        'customer_details',
        'notes',
        'response',
        'response_message',
        'bill_addr',
        'qb_id',
        'status',
        'deleted_at',

    ];
}
