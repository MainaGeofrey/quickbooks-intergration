<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DB_Vendor extends Model
{
    use HasFactory;

    protected $table  = 'vendors';
    protected $primaryKey = 'vendor_id';

    protected $fillable = [
        'client_id',
        'vendor_name',
        'account_number',
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
