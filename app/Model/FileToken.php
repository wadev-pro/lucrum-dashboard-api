<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class FileToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'report_id', 'token'
    ];
}
