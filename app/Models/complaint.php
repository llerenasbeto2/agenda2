<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class complaint extends Model
{
    //
    use HasFactory;
    //protected $table = 'complaints';
    //public $timestamps = false;
    const UPDATED_AT = null;

    protected $fillable = [
        'name_user',
        'sports_id',
        'complaint_text',
    ];

    public function sportField()
    {
        return $this->belongsTo(SportField::class, 'sports_id');
    }
}
