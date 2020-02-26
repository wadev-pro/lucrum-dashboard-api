<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Did extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'did_code';
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = array(
    );

    /**
     * Get dids
     */

    public function did_pool() {
        return $this->belongsTo('App\Model\DidPool');
    }

    /**
     * Get message gateway provider
     */

    public function message_gateway_provider() {
        return $this->belongsTo('App\Model\MessageGatewayProvider', 'message_gateway_provider_id');
    }
}
