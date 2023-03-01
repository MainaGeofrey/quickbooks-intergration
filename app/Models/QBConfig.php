<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QBConfig extends Model
{
    use HasFactory;

    protected $table  = 'q_b_tokens';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'access_token',
        'expires_in',
        'refresh_token',
        'realm_id',
        'client_id',
        'client_secret',

    ];

}
