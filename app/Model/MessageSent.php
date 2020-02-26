<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MessageSent extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'sent';
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = array(
    );
}
