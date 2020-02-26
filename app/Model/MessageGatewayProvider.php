<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MessageGatewayProvider extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'message_gateway_provider';
    protected $primaryKey = 'provider_id';
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
