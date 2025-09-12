<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class faculty extends Model
{
    //
    use HasFactory;
    protected $table = 'faculties';
    //public $timestamps = false;
    protected $fillable = [
        'name',
        'location',
        'responsible',
        'email',
        'phone',
        'municipality_id',
        'capacity',
        'services',
        'description',
        'web_site',
        'image',
    ];

      public function getImageAttribute($value)
    {
        if (!$value) {
            return asset('images/default-faculty.png'); // Imagen predeterminada
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value; // URL externa
        }

        return asset('storage/' . $value); // Ruta relativa: storage/faculties/xxx.jpg
    }
    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible', 'id');
    }
  

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

   public function classrooms()
    {
        return $this->hasMany(Classroom::class, 'faculty_id', 'id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'responsible_id', 'responsible');
    }

    public function reservations()
    {
        return $this->hasMany(reservation_classroom::class, 'Faculty_id');
    }
}
