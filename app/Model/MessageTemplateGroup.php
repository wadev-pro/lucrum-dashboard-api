<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MessageTemplateGroup extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'message_template_group';
    protected $primaryKey = 'group_id';
    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = array(
    );
}
