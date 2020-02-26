<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MessageFailed extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'sending_failed';
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = array(
    );
}
