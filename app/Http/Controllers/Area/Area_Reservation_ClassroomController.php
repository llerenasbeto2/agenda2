<?php

namespace App\Http\Controllers\Area;

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

class Area_Reservation_ClassroomController extends Controller
{
    //
   public function index(Request $request)
    {
        $user = Auth::user();
        $query = reservation_classroom::with(['classroom', 'category', 'user', 'faculty', 'municipality'])
            ->select([
                'id',
                'event_title',
                'status',
                'municipality_id',
                'start_datetime',
                'end_datetime',
                'full_name',
                'Email',
                'phone',
                'classroom_id',
                'category_type',
                'irregular_dates',
                'requirements',
                'attendees',
                'faculty_id',
                'cost',
                'is_paid',
                'payment_date'
            ]);

        if ($user && $user->responsible_id) {
            $query->where('classroom_id', $user->responsible_id);
        }

        if ($request->has('event_title') && !empty($request->event_title)) {
            $query->where('event_title', 'like', '%' . $request->event_title . '%');
        }

        $reservaciones = $query->orderBy('start_datetime', 'desc')->get();

        $reservaciones->transform(function ($reservacion) {
            if ($reservacion->irregular_dates && is_string($reservacion->irregular_dates)) {
                $reservacion->irregular_dates = json_decode($reservacion->irregular_dates, true);
            }
            if ($reservacion->recurring_days && is_string($reservacion->recurring_days)) {
                $reservacion->recurring_days = json_decode($reservacion->recurring_days, true);
            }
            return $reservacion;
        });

        return Inertia::render('Admin/Area/Dashboard', [
            'reservaciones' => $reservaciones,
            'faculties' => Faculty::select('id', 'name')->get(),
            'classrooms' => Classroom::select('id', 'name', 'faculty_id')->get(),
            'categorie' => Categorie::select('id', 'name')->get(),
            'municipalities' => Municipality::select('id', 'name')->get(),
            'auth' => [
                'user' => [
                    'responsible' => $user->responsible_id,
                ],
            ],
            'filters' => $request->only(['event_title'])
        ]);
    }

    public function cambiarEstado(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:Pendiente,Aprobado,Rechazado,Cancelado,No_realizado,Realizado',
        ]);

        $reservation = reservation_classroom::findOrFail($id);
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
        $validated = $request->validate([
            'full_name' => 'nullable|string|max:255',
            'Email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'faculty_id' => 'nullable|exists:faculties,id',
            'municipality_id' => 'nullable|exists:municipality,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'event_title' => 'nullable|string|max:255',
            'category_type' => 'nullable|exists:categories,id',
            'attendees' => 'nullable|integer|min:1',
            'start_datetime' => 'nullable|date',
            'end_datetime' => 'nullable|date|after:start_datetime',
            'requirements' => 'nullable|string',
            'status' => 'required|string|in:Pendiente,Aprobado,Rechazado,Cancelado,No_realizado,Realizado',
            'is_recurring' => 'nullable|boolean',
            'repeticion' => 'nullable|integer|min:1',
            'recurring_frequency' => 'nullable|string|in:daily,weekly,biweekly,monthly',
            'recurring_days' => 'nullable|json',
            'recurring_end_date' => 'nullable|date|after:start_datetime',
            'irregular_dates' => 'nullable|json',
        ]);

        if ($request->has('irregular_dates')) {
            if ($request->irregular_dates === null || $request->irregular_dates === '') {
                $validated['irregular_dates'] = null;
            } else {
                $irregularDates = json_decode($request->irregular_dates, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($irregularDates)) {
                    return back()->withErrors(['irregular_dates' => 'El formato de las fechas irregulares no es válido']);
                }
                $validated['irregular_dates'] = json_encode($irregularDates);
            }
        }

        if ($request->has('recurring_days')) {
            if ($request->recurring_days === null || $request->recurring_days === '') {
                $validated['recurring_days'] = null;
            } else {
                $recurringDays = json_decode($request->recurring_days, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($recurringDays)) {
                    return back()->withErrors(['recurring_days' => 'El formato de los días recurrentes no es válido']);
                }
                $validated['recurring_days'] = json_encode($recurringDays);
            }
        }

        $reservacion->update($validated);

        return redirect()->route('admin.area.dashboard')->with('success', 'Reservación actualizada correctamente');
    }

    public function store(Request $request)
    {
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

        $irregularDates = null;
        if ($request->filled('irregular_dates')) {
            try {
                $decodedDates = json_decode($request->irregular_dates, true);
                if (is_array($decodedDates)) {
                    $irregularDates = array_filter(array_map(function($date) {
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
                Log::error('Error procesando fechas irregulares: ' . $e->getMessage());
                return back()->withInput()->with('error', 'Error al procesar fechas irregulares.');
            }
        }

        $reservationData = [
            'user_id' => $request->user_id,
            'full_name' => htmlspecialchars($request->full_name, ENT_QUOTES, 'UTF-8'),
            'Email' => $request->email,
            'phone' => $request->phone,
            'faculty_id' => $request->faculty_id,
            'municipality_id' => Auth::user()->municipality_id,
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
            $reservation = reservation_classroom::create($reservationData);
            if (class_exists(\Spatie\Activitylog\ActivitylogServiceProvider::class)) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($reservation)
                    ->log('Reservación creada');
            }
            return redirect()->route('admin.area.dashboard')
                ->with('success', 'Reservación creada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al crear reservación: ' . $e->getMessage(), [
                'data' => $reservationData,
                'exception' => $e
            ]);
            return back()->withInput()
                ->with('error', 'Ocurrió un error al crear la reservación. Por favor intente nuevamente.');
        }
    }

    public function destroy($id)
    {
        $reservacion = reservation_classroom::findOrFail($id);
        $reservacion->delete();

        return redirect()->route('admin.area.dashboard')
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
        $approvedReservations = reservation_classroom::where('status', 'Aprobado')
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

            return response()->json(['message' => 'Detalles de pago actualizados correctamente']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validación fallida', 'details' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Reservación no encontrada'], 404);
        } catch (\Exception $e) {
            Log::error('Error al actualizar pago: ' . $e->getMessage(), [
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
