<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'message_template';
    protected $primaryKey = 'template_id';
    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = array(
    );
}
