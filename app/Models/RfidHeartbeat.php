<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidHeartbeat extends Model
{
    protected $table = 'rfid_heartbeats';

    public $timestamps = false;

    protected $fillable = ['reader_name', 'ip_address', 'heartbeat_datetime', 'heartbeat_sequence_number'];

}
