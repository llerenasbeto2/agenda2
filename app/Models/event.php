<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class event extends Model
{
    //
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'title',
        'category_id',
        'date',
        'organizer_id',
        'space_type',
    ];

     

    public $timestamps = false;
}
