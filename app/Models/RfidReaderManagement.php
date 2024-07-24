<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfidReaderManagement extends Model
{
    use HasFactory;

    protected $table = 'rfid_reader_managements';

    protected $fillable = [
        'name',
        'project_id',
        'location_1_id',
        'location_2_id',
        'location_3_id',
        'location_4_id',
        'used_for_attendance',
    ];

    /**
     * Get all of the readers's tagReads.
     */
    public function tagReads()
    {
        return $this->hasMany(RfidTagRead::class, 'reader_name', 'name');
    }

    /**
     * Get reader's pairing.
     */
    public function pairing()
    {
        return $this->hasOne(RfidReaderPairing::class, 'reader_id', 'id');
    }

    /**
     * Get all of the readers's heartbeats.
     */
    public function heartbeats()
    {
        return $this->hasMany(RfidHeartbeat::class, 'reader_name', 'name');
    }

    public function project()
    {
        return $this->hasOne(MasterProject::class, 'id', 'project_id');
    }

    public function locationOne()
    {
        return $this->hasOne(MasterLocation::class, 'id', 'location_1_id');
    }

    public function locationTwo()
    {
        return $this->hasOne(MasterLocation::class, 'id', 'location_2_id');
    }

    public function locationThree()
    {
        return $this->hasOne(MasterLocation::class, 'id', 'location_3_id');
    }

    public function locationFour()
    {
        return $this->hasOne(MasterLocation::class, 'id', 'location_4_id');
    }

    /**
     * Check if device is online by timestamp
     */
    public function isOnline($maxTimestamp)
    {
        $time = [];

        if (!count($maxTimestamp)) {
            $time['display'] = 'No Hearbeat Recorded';
            $time['isOnline'] = false;
        } else {
            if ($maxTimestamp->first()->max_heartbeat_datetime) {
                $heartbeat = Carbon::parse($maxTimestamp->first()->max_heartbeat_datetime);

                if ($heartbeat->lessThan(now()->subMinutes((int) config('qubit.reader_minutes_offline', 5)))) {
                    $time['isOnline'] = false;
                } else {
                    $time['isOnline'] = true;
                }

                $time['display'] = $heartbeat->tz('Asia/Kuala_Lumpur')->toDayDateTimeString();
            }
        }

        return $time;
    }

    /**
     * Interact with the reader's name.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $name = '';

                if (isset($this->project->name)) {
                    $name .= $this->project->name;
                }

                if (isset($this->locationOne->name)) {
                    $name .= '_' . $this->locationOne->name;
                }

                if (isset($this->locationTwo->name)) {
                    $name .= '_' . $this->locationTwo->name;
                }

                if (isset($this->locationThree->name)) {
                    $name .= '_' . $this->locationThree->name;
                }

                if (isset($this->locationFour->name)) {
                    $name .= '_' . $this->locationFour->name;
                }

                return $name;
            },
        );
    }
}
