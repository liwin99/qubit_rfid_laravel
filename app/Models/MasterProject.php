<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'daily_period_from',
        'daily_period_to',
    ];
}
