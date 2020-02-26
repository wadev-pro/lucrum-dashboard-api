<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DidPool extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'did_pool';
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = array(
    );

    /**
     * get dids
     */

    public function dids() {
        return $this->hasMany('App\Model\Did');
    }
}
