<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillItem extends Model
{
    use HasFactory;

    protected $table =  'Bills';

    protected  $fillable  = [
        'VendorName',
        'VendorCode',
        'RefrenceNo',
        'DueDate',
        'Amount',
        'ItemName',
        'ItemDescription',
        'ItemCode',
        'Quantity',
        'UnitPrice',
    ];


}
