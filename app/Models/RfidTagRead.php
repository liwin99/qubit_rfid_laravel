<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfidTagRead extends Model
{
    use HasFactory;

    protected $table = 'rfid_tag_reads';

    public $timestamps = false;

    protected $fillable = ['antenna', 'protocol', 'rssi', 'reader_name', 'epc', 'first_seen_timestamp',
        'last_seen_timestamp', 'bank_data', 'read_count', 'tag_read_datetime', 'unique_hash'];

    protected $hidden = ['unique_hash'];
}
