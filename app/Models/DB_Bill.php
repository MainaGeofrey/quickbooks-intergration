<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DB_Bill extends Model
{
    use HasFactory;

    protected $table  = 'bills';
    protected $primaryKey = 'bill_id';

    protected $fillable = [
        'client_id',
        'vendor_name',
        'reference_number',
        'due_date',
        'date_created',
        'response',
        'response_message',
        'qb_id',
        'status',
        'deleted_at',
        'line_items',

    ];
}
