<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    use HasFactory;

    protected $table  = 'payments';
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'client_id',
        'account_name',
        'mobile_number',
        'reference_number',
        'amount',
        'date_time',
        'notes',
        'processed',
        'date_time',
        'response',
        'response_message',
        'qb_id',
        'status',
        'deleted_at',
        'line_items',

    ];
}
