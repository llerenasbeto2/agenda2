<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class sport_field extends Model
{
    //
    use HasFactory;
    protected $table = 'sport_field';
    protected $fillable = [
        'name',
        'location',
        'capacity',
        'services',
        'responsible',
        'email',
        'phone',
    ];

    public function reservations()
    {
        return $this->hasMany(ReservationSport::class, 'sport_id');
    }

    public function complaints()
    {
        return $this->hasMany(ComplaintSport::class, 'sports_id');
    }

}
