<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MessageProcessingFailed extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'processing_failed';
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = array(
    );
}
