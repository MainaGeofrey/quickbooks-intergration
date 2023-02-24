<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use  Illuminate\Support\Str;
class BaseModel extends Model implements 
{
    use HasFactory;
    use AuditableTrait;
     //use SoftDeletes;
    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var bool
     */
     protected $casts = [
     'created_at' => 'date',
     'updated_at' => 'date',
     'client_id' => 'integer',
 ];
     public function creator(){
          return $this->belongsTo('App\User','created_by');
      }
      public function editor(){
          return $this->belongsTo('App\User','updated_by');
      }

    protected static function boot() {
           parent::boot();
           static::creating(function($model)
            {
                $user = Auth::user();
                $model->created_by = $user->id;
                $model->updated_by = $user->id;
                 $model->uuid = str_replace("-","",Str::uuid());
                 //if model has column then add it and model is not clients table
                 $baseClass = class_basename($model);


            });
            static::updating(function($model)
            {
                $user_id = Auth::user()->id??0;
                $model->updated_by = $user_id;
            });


            static::deleting(function($model)
            {
                $user = Auth::user();
                $model->status =5;
                  $model->updated_by = $user->id;
                  $model->save();
                    return true;

          });

          static::deleted(function($model)
          {
              $user = Auth::user();
              $model->status =5;
                $model->updated_by = $user->id;
                $model->save();
                  return true;

        });

       }


}
