<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class reservation_classroom extends Model
{
    //
    /*
        'start_datetime',  // Toma el valor del dia que selecciona el usuario del calendario
        'end_datetime',    // toma el valor del dia en que finalizara el evento, en caso de no repetirse el evento tendra lo mismo que start_datetime.
        'repeticion',    // cantidad de veces que se repetira, ejemplo: 4  -  debe ser NULL cuando irregular_dates tiene datos
        'recurring_frequency',   // como se va a repetir el evento: monthly-weekly  -  debe ser NULL cuando irregular_dates tiene datos o el usuario solo quiere agendar un dia o dias de su elección
        'recurring_days',        // los dias de la semana que se repetira: Monday - Friday  -  debe ser NULL cuando irregular_dates tiene datos o el usuario solo quiere agendar un día o dias de su elección
        'recurring_end_date',    // fecha de termino deseada, ejemplo: 2025-07-09,  -  debe ser NULL cuando irregular_dates tiene datos  -  esta columna solo se llena si el evento es repetible
        'irregular_dates',       // fechas irregulares seleccionadas por el usuario, ejemplo:  [{"date": "2025-10-05", "endTime": "12:00", "startTime": "10:00", "displayText": "2025-10-05 10:00 - 12:00"}] -  debe ser NULL cuando el evento se repite (columna repeticion)
    */
    use HasFactory;

    protected $table = 'reservations_classrooms';

    protected $fillable = [
        'user_id', 'full_name', 'Email', 'phone', 'faculty_id', 'municipality_id',
        'classroom_id', 'event_title', 'category_type', 'attendees', 'start_datetime',
        'end_datetime', 'requirements', 'status', 'is_recurring', 'repeticion',
        'recurring_frequency', 'recurring_days', 'recurring_end_date', 'irregular_dates'
    ];

    // Definimos los campos que deben ser tratados como JSON
    protected $casts = [
        'cost' => 'decimal:2',
        'payment_date' => 'date',    
        'irregular_dates' => 'array',
        'recurring_days' => 'array',
        'is_recurring' => 'boolean',
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'recurring_end_date' => 'datetime',
    ];

    /**
     * Relación con el usuario (User)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el aula (Classroom)
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Relación con la facultad (Faculty)
     */
    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Relación con el municipio (Municipality)
     */
    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * Relación con la categoría (Category)
     */
    public function category()
    {
        return $this->belongsTo(Categorie::class, 'category_type');
    }


    public function setIrregularDatesAttribute($value)
    {
        $this->attributes['irregular_dates'] = is_array($value) 
            ? json_encode($value, JSON_UNESCAPED_UNICODE) 
            : $value;
    }

}
