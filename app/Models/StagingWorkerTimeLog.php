<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StagingWorkerTimeLog extends Model
{
    use HasFactory;

    protected $table = 'staging_worker_time_logs';

    protected $fillable = [
        'reader_1_id',
        'reader_1_name',
        'reader_2_id',
        'reader_2_name',
        'epc',
        'project_id',
        'project_name',
        'tag_read_datetime',
        'direction',
        'period',
    ];
}
