<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class reservation_sport extends Model
{
    //
    use HasFactory;
    protected $table = 'reservations_sports';
   /// protected $table = 'reservations_sports';
   // public $timestamps = false;
   protected $fillable = [
    'user_id',
    'full_name',
    'phone',
    'sport_id',
    'event_title',
    'event_type',
    'attendees',
    'start_datetime',
    'end_datetime',
    'requirements',
    'status',
];

protected $casts = [
    'start_datetime' => 'datetime',
    'end_datetime' => 'datetime',
];

public function user()
{
    return $this->belongsTo(User::class);
}

public function sportField()
{
    return $this->belongsTo(SportField::class, 'sport_id');
}
}
