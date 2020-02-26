<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'campaign';
    protected $primaryKey = 'campaign_id';
    protected $keyType = 'string';

    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = array(
    );

}
