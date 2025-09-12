<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Municipality extends Model
{
    use HasFactory;

    protected $table = 'municipality';

    protected $fillable = ['name'];

    public function faculties()
    {
        return $this->hasMany(Faculty::class);
    }

    public function categories()
    {
        return $this->hasMany(Categorie::class);
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }

}
