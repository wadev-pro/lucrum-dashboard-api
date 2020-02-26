<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MessageFiltered extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'filtered';
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = array(
    );
}
