<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class classroom extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'faculty_id',
        'name',
        'capacity',
        'services',
        'responsible',
        'email',
        'phone',
        'image_url', 'image_path', 'uses_db_storage', 'faculty_id'
    ];

   public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible', 'id');
    }
    

    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id', 'id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation_Classroom::class);
    }

    public function complaints()
    {
        return $this->hasMany(complaint_classroom::class);
    }

         public function complaint_classrooms()
    {
        return $this->hasMany(complaint_classroom::class);
    }
         public function reservationsClassrooms()
    {
        return $this->hasMany(reservation_classroom::class);
    }
}
