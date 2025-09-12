<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RecurringPattern extends Model
{
    //

    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'reservation_id',
        'repeticion',
        'recurring_frequency',
        'recurring_days',
        'recurring_end_date',
        'irregular_dates',
    ];

    protected $casts = [
        'recurring_end_date' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(reservation_classroom::class, 'reservation_id');
    }
}
