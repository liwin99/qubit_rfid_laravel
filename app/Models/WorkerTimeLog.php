<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerTimeLog extends Model
{
    use HasFactory;

    protected $table = 'worker_time_logs';

    protected $fillable = [
        'reader_1_id',
        'reader_1_name',
        'reader_2_id',
        'reader_2_name',
        'epc',
        'project_id',
        'project_name',
        'clock_in',
        'clock_out',
        'period',
        'last_tag_read',
        'last_synced_to_tms'
    ];
}
