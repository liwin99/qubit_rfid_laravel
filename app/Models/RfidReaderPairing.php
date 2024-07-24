<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfidReaderPairing extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'pair_id',
        'reader_id',
        'reader_position',
    ];

    const CLOSE_TO_EXIT = 1;
    const CLOSE_TO_SITE = 2;

    const POSITION_MAPPING = [
        self::CLOSE_TO_EXIT => '1 : Closer to exit',
        self::CLOSE_TO_SITE => '2 : Closer to site',
    ];

    /**
     * Get reader.
     */
    public function reader()
    {
        return $this->belongsTo(RfidReaderManagement::class, 'reader_id', 'id');
    }

    /**
     * Get nextPairId.
     */
    public static function nextPairId()
    {
        // Find the maximum pair ID currently in use
        $maxPairId = RfidReaderPairing::max('pair_id');

        if ($maxPairId === null) {
            $nextPairId = 1;
        } else {
            $nextPairId = $maxPairId + 1;
        }

        return $nextPairId;
    }
}
