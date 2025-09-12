<?php

namespace App\Http\Controllers\General;

use App\Mail\ReservationCommentMail;
use Illuminate\Support\Facades\Mail;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\reservation_classroom;
use App\Models\classroom;
use App\Models\Categorie;
use App\Models\faculty;
use App\Models\municipality;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class Reservation_ClassroomController extends Controller
{
public function index(Request $request)
{
    $query = reservation_classroom::with(['classroom', 'category', 'user', 'faculty', 'municipality'])
        ->select(
            'id', 'event_title', 'status', 'municipality_id', 'start_datetime', 'end_datetime',
            'full_name', 'Email', 'phone', 'classroom_id', 'category_type', 
            'irregular_dates', 'requirements', 'attendees', 'faculty_id','cost', 'is_paid', 'payment_date'

        );

    if ($request->has('event_title') && !empty($request->event_title)) {
        $query->where('event_title', 'like', '%' . $request->event_title . '%');
    }

    $reservaciones = $query->orderBy('start_datetime', 'desc')->get();

    $reservaciones->transform(function ($reservacion) {
        if ($reservacion->irregular_dates && is_string($reservacion->irregular_dates)) {
            $reservacion->irregular_dates = json_decode($reservacion->irregular_dates, true);
        }
        return $reservacion;
    });

    $faculties = Faculty::select('id', 'name', 'municipality_id')->get()->map(function ($faculty) {
        return [
            'id' => (int) $faculty->id,
            'name' => $faculty->name,
            'municipality_id' => $faculty->municipality_id !== null ? (int) $faculty->municipality_id : null
        ];
    })->values();

    $classrooms = Classroom::select('id', 'name', 'faculty_id')->get()->map(function ($classroom) {
        return [
            'id' => (int) $classroom->id,
            'name' => $classroom->name,
            'faculty_id' => $classroom->faculty_id !== null ? (int) $classroom->faculty_id : null
        ];
    })->values();

    return Inertia::render('Admin/General/Dashboard', [
        'reservaciones' => $reservaciones,
        'faculties' => $faculties,
        'classrooms' => $classrooms,
        'categorie' => Categorie::select('id', 'name')->get()->map(function ($category) {
            return ['id' => (int) $category->id, 'name' => $category->name];
        })->values(),
        'municipalities' => Municipality::select('id', 'name')->get()->map(function ($municipality) {
            return ['id' => (int) $municipality->id, 'name' => $municipality->name];
        })->values(),
        'filters' => $request->only(['event_title'])
    ]);
}
    public function cambiarEstado(Request $request, $id)
{
    $request->validate([
        'status' => 'required|string|in:Pendiente,Aprobado,Rechazado,Cancelado,No_realizado,Realizado',
    ]);

    $reservation = Reservation_Classroom::findOrFail($id);
    $reservation->status = $request->status;
    $reservation->save();

    return back()->with('success', 'Estado de la reservación actualizado correctamente.');
} 
public function edit(reservation_classroom $reservacion)
{
    $reservacion->load(['faculty', 'classroom', 'category', 'municipality']);

    return response()->json([
        'reservation' => [
            'id' => (int) $reservacion->id,
            'full_name' => $reservacion->full_name,
            'Email' => $reservacion->Email,
            'phone' => $reservacion->phone,
            'faculty_id' => $reservacion->faculty_id !== null ? (int) $reservacion->faculty_id : null,
            'municipality_id' => $reservacion->municipality_id !== null ? (int) $reservacion->municipality_id : null,
            'classroom_id' => $reservacion->classroom_id !== null ? (int) $reservacion->classroom_id : null,
            'event_title' => $reservacion->event_title,
            'category_type' => $reservacion->category_type !== null ? (int) $reservacion->category_type : null,
            'attendees' => $reservacion->attendees,
            'start_datetime' => $reservacion->start_datetime ? $reservacion->start_datetime->toIso8601String() : null,
            'end_datetime' => $reservacion->end_datetime ? $reservacion->end_datetime->toIso8601String() : null,
            'requirements' => $reservacion->requirements ?? '',
            'status' => $reservacion->status,
            'is_recurring' => $reservacion->is_recurring,
            'repeticion' => $reservacion->repeticion,
            'recurring_frequency' => $reservacion->recurring_frequency,
            'irregular_dates' => $reservacion->irregular_dates ? json_decode($reservacion->irregular_dates, true) : null,
            'recurring_end_date' => $reservacion->recurring_end_date ? $reservacion->recurring_end_date->toIso8601String() : null,
            'recurring_days' => $reservacion->recurring_days ? json_decode($reservacion->recurring_days, true) : null,
             'cost' => $reservacion->cost ? (float) $reservacion->cost : 0.00,
            'is_paid' => (bool) $reservacion->is_paid,
            'payment_date' => $reservacion->payment_date ? $reservacion->payment_date->format('Y-m-d') : null,

        ],
        'faculties' => Faculty::select('id', 'name', 'municipality_id')->get()->map(function ($faculty) {
            return [
                'id' => (int) $faculty->id,
                'name' => $faculty->name,
                'municipality_id' => $faculty->municipality_id !== null ? (int) $faculty->municipality_id : null
            ];
        })->values(),
        'classrooms' => Classroom::select('id', 'name', 'faculty_id')->get()->map(function ($classroom) {
            return [
                'id' => (int) $classroom->id,
                'name' => $classroom->name,
                'faculty_id' => $classroom->faculty_id !== null ? (int) $classroom->faculty_id : null
            ];
        })->values(),
        'categorie' => Categorie::select('id', 'name')->get()->map(function ($category) {
            return ['id' => (int) $category->id, 'name' => $category->name];
        })->values(),
        'municipalities' => Municipality::select('id', 'name')->get()->map(function ($municipality) {
            return ['id' => (int) $municipality->id, 'name' => $municipality->name];
        })->values(),
    ]);
}

public function update(Request $request, reservation_classroom $reservacion)
{
    // Validación más flexible - todos los campos son nullable excepto status
    $validated = $request->validate([
        'full_name' => 'nullable|string|max:255',
        'Email' => 'nullable|email|max:255',
        'phone' => 'nullable|string|max:255',
        'faculty_id' => 'nullable|integer|exists:faculties,id',
        'municipality_id' => 'nullable|integer|exists:municipality,id',
        'classroom_id' => 'nullable|integer|exists:classrooms,id',
        'event_title' => 'nullable|string|max:255',
        'category_type' => 'nullable|integer|exists:categories,id',
        'attendees' => 'nullable|integer|min:1',
        'start_datetime' => 'nullable|date',
        'end_datetime' => 'nullable|date',
        'requirements' => 'nullable|string',
        'status' => 'required|string|in:Pendiente,Aprobado,Rechazado,Cancelado,No_realizado,Realizado',
        'is_recurring' => 'nullable|boolean',
        'repeticion' => 'nullable|integer|min:1',
        'recurring_frequency' => 'nullable|string|in:daily,weekly,biweekly,monthly',
        'recurring_days' => 'nullable|string',
        'recurring_end_date' => 'nullable|date',
        'irregular_dates' => 'nullable|string',
        'cost' => 'nullable|numeric|min:0',
        'is_paid' => 'nullable|boolean',
        'payment_date' => 'nullable|date',
    ]);

    // Log para debugging
    \Log::info('Datos recibidos para actualizar:', $request->all());
    \Log::info('Datos validados:', $validated);

    // Manejar fechas de inicio y fin
    if (!empty($validated['start_datetime'])) {
        // Convertir el formato datetime-local a formato ISO
        if (strpos($validated['start_datetime'], 'Z') === false) {
            $validated['start_datetime'] = date('Y-m-d H:i:s', strtotime($validated['start_datetime']));
        }
    }

    if (!empty($validated['end_datetime'])) {
        // Convertir el formato datetime-local a formato ISO
        if (strpos($validated['end_datetime'], 'Z') === false) {
            $validated['end_datetime'] = date('Y-m-d H:i:s', strtotime($validated['end_datetime']));
        }
    }

    // Validar que end_datetime sea posterior a start_datetime solo si ambas están presentes
    if (!empty($validated['start_datetime']) && !empty($validated['end_datetime'])) {
        $startTime = strtotime($validated['start_datetime']);
        $endTime = strtotime($validated['end_datetime']);
        
        if ($endTime <= $startTime) {
            return back()->withErrors(['end_datetime' => 'La fecha de fin debe ser posterior a la fecha de inicio']);
        }
    }

    // Manejar fechas irregulares
    if ($request->has('irregular_dates')) {
        if ($request->irregular_dates === null || $request->irregular_dates === '' || $request->irregular_dates === 'null') {
            $validated['irregular_dates'] = null;
        } else {
            try {
                $irregularDates = json_decode($request->irregular_dates, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return back()->withErrors(['irregular_dates' => 'El formato de las fechas irregulares no es válido']);
                }
                
                if (!is_array($irregularDates)) {
                    return back()->withErrors(['irregular_dates' => 'Las fechas irregulares deben ser un array válido']);
                }
                
                // Validar cada fecha irregular
                foreach ($irregularDates as $index => $date) {
                    if (!isset($date['date']) || !isset($date['startTime']) || !isset($date['endTime'])) {
                        return back()->withErrors(['irregular_dates' => "Fecha irregular #{$index} incompleta"]);
                    }
                }
                
                $validated['irregular_dates'] = json_encode($irregularDates);
            } catch (\Exception $e) {
                \Log::error('Error al procesar fechas irregulares:', ['error' => $e->getMessage()]);
                return back()->withErrors(['irregular_dates' => 'Error al procesar las fechas irregulares']);
            }
        }
    }

    // Manejar días recurrentes
    if ($request->has('recurring_days')) {
        if ($request->recurring_days === null || $request->recurring_days === '' || $request->recurring_days === 'null') {
            $validated['recurring_days'] = null;
        } else {
            try {
                $recurringDays = json_decode($request->recurring_days, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return back()->withErrors(['recurring_days' => 'El formato de los días recurrentes no es válido']);
                }
                $validated['recurring_days'] = json_encode($recurringDays);
            } catch (\Exception $e) {
                \Log::error('Error al procesar días recurrentes:', ['error' => $e->getMessage()]);
                return back()->withErrors(['recurring_days' => 'Error al procesar los días recurrentes']);
            }
        }
    }

    // Convertir valores vacíos a null para campos específicos
    $nullableFields = [
        'full_name', 'Email', 'phone', 'faculty_id', 'municipality_id', 'classroom_id', 
        'event_title', 'category_type', 'attendees', 'start_datetime', 'end_datetime', 
        'requirements', 'is_recurring', 'repeticion', 'recurring_frequency', 
        'recurring_end_date', 'cost', 'payment_date'
    ];

    foreach ($nullableFields as $field) {
        if (isset($validated[$field]) && $validated[$field] === '') {
            $validated[$field] = null;
        }
    }

    // Convertir strings a enteros para campos numéricos
    $numericFields = ['faculty_id', 'municipality_id', 'classroom_id', 'category_type', 'attendees'];
    foreach ($numericFields as $field) {
        if (isset($validated[$field]) && $validated[$field] !== null && $validated[$field] !== '') {
            $validated[$field] = (int) $validated[$field];
        }
    }

    // Convertir strings a boolean para campos booleanos
    if (isset($validated['is_recurring'])) {
        $validated['is_recurring'] = filter_var($validated['is_recurring'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    if (isset($validated['is_paid'])) {
        $validated['is_paid'] = filter_var($validated['is_paid'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    // Convertir cost a float si está presente
    if (isset($validated['cost']) && $validated['cost'] !== null) {
        $validated['cost'] = (float) $validated['cost'];
    }

    try {
        // Actualizar la reservación con todos los campos validados
        $reservacion->update($validated);
        
        \Log::info('Reservación actualizada exitosamente:', ['id' => $reservacion->id]);

        return redirect()->route('admin.general.dashboard')
            ->with('success', 'Reservación actualizada correctamente');
            
    } catch (\Exception $e) {
        \Log::error('Error al actualizar reservación:', [
            'error' => $e->getMessage(),
            'data' => $validated,
            'reservation_id' => $reservacion->id
        ]);
        
        return back()
            ->withInput()
            ->withErrors(['general' => 'Error al actualizar la reservación: ' . $e->getMessage()]);
    }
}


     public function store(Request $request)
{
    // Validation rules
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'full_name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'required|string|max:20',
        'faculty_id' => 'required|exists:faculties,id',
        'classroom_id' => 'required|exists:classrooms,id',
        'event_title' => 'required|string|max:255',
        'category_type' => 'required|exists:categories,id',
        'attendees' => 'required|integer|min:1|max:500',
        'start_datetime' => 'required|date|after_or_equal:now',
        'end_datetime' => 'required|date|after:start_datetime',
        'requirements' => 'nullable|string',
        'is_recurring' => 'boolean',
        'repeticion' => 'nullable|integer|min:1|max:52',
        'recurring_frequency' => 'nullable|string|in:weekly,monthly',
        'recurring_days' => 'nullable|string',
        'recurring_end_date' => 'nullable|date|after:start_datetime',
        'irregular_dates' => [
            'nullable',
            'string',
            function ($attribute, $value, $fail) {
                if (!empty($value)) {
                    $decoded = json_decode($value, true);
                    if ($decoded === null) {
                        $fail('El formato de las fechas irregulares no es válido.');
                    }
                }
            },
        ],
    ]);

    // Verificar disponibilidad del aula
    $conflictingReservations = reservation_classroom::where('classroom_id', $request->classroom_id)
        ->where(function($query) use ($request) {
            $query->whereBetween('start_datetime', [$request->start_datetime, $request->end_datetime])
                  ->orWhereBetween('end_datetime', [$request->start_datetime, $request->end_datetime])
                  ->orWhere(function($q) use ($request) {
                      $q->where('start_datetime', '<', $request->start_datetime)
                        ->where('end_datetime', '>', $request->end_datetime);
                  });
        })
        ->whereNotIn('status', ['Rechazado', 'Cancelado'])
        ->exists();

    if ($conflictingReservations) {
        return back()->withInput()->with('error', 'El aula no está disponible en el horario seleccionado.');
    }

    // Procesar fechas irregulares
    $irregularDates = null;
    if ($request->filled('irregular_dates')) {
        try {
            $decodedDates = json_decode($request->irregular_dates, true);
            
            if (is_array($decodedDates)) {
                $irregularDates = array_filter(array_map(function($date) {
                    // Validar que los campos necesarios estén presentes
                    if (!isset($date['date'], $date['startTime'], $date['endTime'])) {
                        return null;
                    }
                    
                    return [
                        'date' => $date['date'],
                        'startTime' => $date['startTime'],
                        'endTime' => $date['endTime'],
                        'displayText' => "{$date['date']} {$date['startTime']} - {$date['endTime']}"
                    ];
                }, $decodedDates));
            }
        } catch (\Exception $e) {
            \Log::error('Error procesando fechas irregulares: '.$e->getMessage());
            return back()->withInput()->with('error', 'Error al procesar fechas irregulares.');
        }
    }

    // Preparar datos de la reservación
    $reservationData = [
        'user_id' => $request->user_id,
        'full_name' => htmlspecialchars($request->full_name, ENT_QUOTES, 'UTF-8'),
        'Email' => $request->email,
        'phone' => $request->phone,
        'faculty_id' => $request->faculty_id,
        'municipality_id' => 1, // Valor predeterminado
        'classroom_id' => $request->classroom_id,
        'event_title' => htmlspecialchars($request->event_title, ENT_QUOTES, 'UTF-8'),
        'category_type' => $request->category_type,
        'attendees' => $request->attendees,
        'start_datetime' => $request->start_datetime,
        'end_datetime' => $request->end_datetime,
        'requirements' => htmlspecialchars($request->requirements ?? '', ENT_QUOTES, 'UTF-8'),
        'status' => 'Pendiente',
        'irregular_dates' => $irregularDates ? json_encode($irregularDates, JSON_UNESCAPED_UNICODE) : null,
    ];

    // Manejar campos recurrentes
    if ($request->is_recurring) {
        $reservationData['is_recurring'] = true;
        $reservationData['repeticion'] = $request->repeticion;
        $reservationData['recurring_frequency'] = $request->recurring_frequency;
        $reservationData['recurring_days'] = is_array($request->recurring_days) 
            ? json_encode($request->recurring_days)
            : $request->recurring_days;
        $reservationData['recurring_end_date'] = $request->recurring_end_date;
    } else {
        $reservationData['is_recurring'] = false;
        $reservationData['repeticion'] = null;
        $reservationData['recurring_frequency'] = null;
        $reservationData['recurring_days'] = null;
        $reservationData['recurring_end_date'] = null;
    }

    try {
        // Crear la reservación
        $reservation = reservation_classroom::create($reservationData);
        
        // Registrar actividad si usas laravel-activitylog
        if (class_exists(\Spatie\Activitylog\ActivitylogServiceProvider::class)) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($reservation)
                ->log('Reservación creada');
        }

        // Redirigir con mensaje de éxito
        return redirect()->route('dashboard')
            ->with('success', 'Reservación creada exitosamente');
            
    } catch (\Exception $e) {
        // Registrar error detallado
        \Log::error('Error al crear reservación: '.$e->getMessage(), [
            'data' => $reservationData,
            'exception' => $e
        ]);
        
        // Regresar con mensaje de error
        return back()->withInput()
            ->with('error', 'Ocurrió un error al crear la reservación. Por favor intente nuevamente.');
    }
}
    
    
     public function destroy($id)
     {
         $reservacion = reservation_classroom::findOrFail($id);
         $reservacion->delete();
         
         return redirect()->route('admin.general.dashboard') // o el nombre de tu ruta de listado
             ->with('success', 'Reservación eliminada exitosamente');
     }


     public function sendComment(Request $request)
{
    \Log::info('Inicio de sendComment', ['request' => $request->all()]);

    $validated = $request->validate([
        'reservation_id' => 'required|exists:reservations_classrooms,id',
        'comment' => 'required|string|max:1000',
    ]);

    try {
        $reservation = Reservation_Classroom::with('classroom')->findOrFail($validated['reservation_id']);
        $sender = auth()->user();

        if (!$sender) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        if (!$reservation->Email || !filter_var($reservation->Email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'La reservación no tiene un correo electrónico válido'], 422);
        }

        \Log::info('Intentando enviar correo simple', [
            'to' => $reservation->Email,
            'from' => config('mail.from.address'),
            'replyTo' => $sender->email,
            'comment' => $validated['comment'],
        ]);

        // Envío simple con Mail::raw
        Mail::raw('Comentario: ' . $validated['comment'], function ($message) use ($reservation, $sender) {
            $message->to($reservation->Email)
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->replyTo($sender->email)
                    ->subject('Notificacion');
        });

        \Log::info('Correo enviado exitosamente');
        return response()->json(['success' => 'Comentario enviado correctamente']);
    } catch (\Exception $e) {
        \Log::error('Error al enviar comentario', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json(['error' => 'Error al enviar el comentario: ' . $e->getMessage()], 500);
    }
}

    public function getApprovedReservations()
        {
            $approvedReservations = reservation_classroom::where('status', 'approved')
                ->orderBy('start_datetime', 'asc')
                ->get(['event_title', 'start_datetime', 'end_datetime']);

            return response()->json($approvedReservations);
        }

public function updatePayment(Request $request)
{
    try {
        $validated = $request->validate([
            'reservation_id' => 'required|exists:reservations_classrooms,id',
            'cost' => 'required|numeric|min:0',
            'is_paid' => 'required|boolean',
            'payment_date' => 'nullable|date'
        ]);

        $reserva = reservation_classroom::findOrFail($request->reservation_id);
        $reserva->cost = $request->cost;
        $reserva->is_paid = $request->is_paid;
        $reserva->payment_date = $request->payment_date;
        $reserva->save();

        return response()->json([
            'message' => 'Detalles de pago actualizados correctamente',
            'data' => [
                'id' => $reserva->id,
                'cost' => $reserva->cost,
                'is_paid' => $reserva->is_paid,
                'payment_date' => $reserva->payment_date
            ]
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['error' => 'Validación fallida', 'details' => $e->errors()], 422);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['error' => 'Reservación no encontrada'], 404);
    } catch (\Exception $e) {
        \Log::error('Error al actualizar pago: ' . $e->getMessage(), [
            'request_data' => $request->all(),
            'exception' => $e->getTraceAsString()
        ]);
        return response()->json([
            'error' => 'Error interno del servidor',
            'details' => $e->getMessage()
        ], 500);
    }
}

}
