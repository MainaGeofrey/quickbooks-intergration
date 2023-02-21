<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    use HasFactory;


    protected $table  = 'api_tokens';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'api_token',
        'expires_at',
        'last_used_at',
    ];

}
