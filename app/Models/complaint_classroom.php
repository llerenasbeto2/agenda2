<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class complaint_classroom extends Model
{
    //
    use HasFactory;
   protected $table = 'complaints_classrooms';
    //public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'email',
        'classroom_id',
        'complaint_text',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}
