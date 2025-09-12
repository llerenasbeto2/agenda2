<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\rol;
use App\Models\faculty;
use App\Models\Municipality;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol_id',
        'municipality_id',
        'responsible_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    //<-------------------------------------->
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

       


    

    // Agregar métodos para verificar roles
    public function isAdministradorGeneral()
    {
        return $this->rol->name === 'Administrador General';
    }

    public function isAdministradorEstatal()
    {
        return $this->rol->name === 'Administrador estatal';
    }

    public function isAdministradorArea()
    {
        return $this->rol->name === 'Administrador area';
    }

    public function isUsuario()
    {
        return $this->rol->name === 'Usuario';
    }

    public function isAdmin()
    {
        return in_array($this->rol->name, [
            'Administrador General', 
            'Administrador estatal', 
            'Administrador area'
        ]);
    }



     // En User.php

  
     public function rol()
     {
         return $this->belongsTo(rol::class, 'rol_id');
     }

     
    public function municipality()
    {
        return $this->belongsTo(Municipality::class, 'municipality_id');
    }
    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }


    public function responsibleFaculty()
{
    return $this->belongsTo(Faculty::class, 'responsible_id');
}

public function responsibleClassroom()
{
    return $this->belongsTo(Classroom::class, 'responsible_id');
}


}
