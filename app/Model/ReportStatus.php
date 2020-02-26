<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ReportStatus extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type', 'user_id', 'filter', 'token', 'status', 'completed_at', 'log', 'filename'
    ];
}
