<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class event_statistic extends Model
{
    //
    use HasFactory;
   // protected $table = 'event_statistics';
    //public $timestamps = false;

    public $timestamps = false;

    protected $fillable = [
        'municipality_id',
        'total_events',
        'total_attendees',
        'last_updated',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];
}
