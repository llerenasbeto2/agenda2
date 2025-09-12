<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class space_view extends Model
{
    //
    use HasFactory;
    public $timestamps = true;
    protected $table = 'space_views';
    //protected $fillable = ['faculty_id', 'name', 'capacity', 'services', 'responsible', 'email', 'phone'];
    protected $fillable = [
        'name',
        'location',
        'capacity',
        'services',
        'responsible',
        'email',
        'phone',
        'image'
    ];
}
