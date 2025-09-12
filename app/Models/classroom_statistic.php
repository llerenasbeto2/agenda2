<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class classroom_statistic extends Model
{
    //
    use HasFactory;
    //protected $table = 'classroom_statistics';
    //public $timestamps = false;

    public $timestamps = false;

    protected $fillable = [
        'municipality_id',
        'classroom_id',
        'faculty_id',
        'total_events',
        'total_attendees',
        'average_attendance',
        'last_event_date',
        'last_updated',
    ];

    protected $casts = [
        'last_event_date' => 'datetime',
        'last_updated' => 'datetime',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }
}
