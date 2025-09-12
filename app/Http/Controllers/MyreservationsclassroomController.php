<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\reservation_classroom;
use App\Models\classroom;
use App\Models\Faculty;
use App\Models\Categorie;
use App\Models\Municipality;
use Illuminate\Support\Facades\Auth;

class MyreservationsclassroomController extends Controller
{
    /**
     * Mostrar el formulario para crear un nuevo evento
     */
    public function create()
    {
        $userData = Auth::check() 
            ? Auth::user()->only(['id', 'name', 'email', 'phone'])
            : null;

        return Inertia::render('Eventos/Create', [
            'userData' => $userData,
            'formData' => [
                'faculties' => Faculty::select('id', 'name', 'responsible', 'municipality_id', 'image', 'web_site')->get(),
                'categories' => Categorie::select('id', 'name')->get(),
                'municipalities' => Municipality::select('id', 'name')->get(),
                'classrooms' => classroom::all(),
                'reservations_classrooms' => reservation_classroom::all(),
            ]
        ]);
    }
    
    /**
     * Obtener facultades por municipio
     */
    public function getFaculties(Request $request)
    {
        $request->validate([
            'municipality_id' => 'required|exists:municipalities,id'
        ]);

        $faculties = Faculty::where('municipality_id', $request->municipality_id)
            ->select('id', 'name', 'municipality_id')
            ->get();

        return response()->json([
            'faculties' => $faculties
        ]);
    }
    
    /**
     * Obtener aulas por facultad
     */
    public function getClassrooms(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:faculties,id'
        ]);

        $classrooms = Classroom::where('faculty_id', $request->faculty_id)
            ->select('id', 'name', 'capacity')
            ->get();

        return response()->json([
            'classrooms' => $classrooms
        ]);
    }

    /**
     * Obtener reservaciones existentes para un aula específica
     */
    public function getExistingReservations(Request $request)
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id'
        ]);

        $reservations = reservation_classroom::where('classroom_id', $request->classroom_id)
            ->where('status', 'Aprobado')
            ->select('id', 'start_datetime', 'end_datetime', 'irregular_dates', 'event_title', 'full_name')
            ->get();

        $existingDates = [];

        foreach ($reservations as $reservation) {
            // Si tiene irregular_dates (eventos repetibles), usar esas fechas
            if (!is_null($reservation->irregular_dates)) {
                $irregularDates = is_string($reservation->irregular_dates) 
                    ? json_decode($reservation->irregular_dates, true) 
                    : $reservation->irregular_dates;
                
                if (is_array($irregularDates)) {
                    foreach ($irregularDates as $date) {
                        $existingDates[] = [
                            'id' => $reservation->id,
                            'title' => $reservation->event_title,
                            'organizer' => $reservation->full_name,
                            'date' => $date['date'],
                            'start_time' => $date['startTime'],
                            'end_time' => $date['endTime'],
                            'start_datetime' => $date['date'] . ' ' . $date['startTime'],
                            'end_datetime' => $date['date'] . ' ' . $date['endTime'],
                        ];
                    }
                }
            } else {
                // Si no tiene irregular_dates, usar start_datetime y end_datetime
                $startDate = \Carbon\Carbon::parse($reservation->start_datetime);
                $endDate = \Carbon\Carbon::parse($reservation->end_datetime);
                
                $existingDates[] = [
                    'id' => $reservation->id,
                    'title' => $reservation->event_title,
                    'organizer' => $reservation->full_name,
                    'date' => $startDate->format('Y-m-d'),
                    'start_time' => $startDate->format('H:i'),
                    'end_time' => $endDate->format('H:i'),
                    'start_datetime' => $reservation->start_datetime,
                    'end_datetime' => $reservation->end_datetime,
                ];
            }
        }

        return response()->json([
            'existing_reservations' => $existingDates
        ]);
    }

    /**
     * Verificar conflictos de horarios
     */
    public function checkConflicts(Request $request)
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'dates' => 'required|array',
            'dates.*.date' => 'required|date',
            'dates.*.start_time' => 'required|string',
            'dates.*.end_time' => 'required|string',
        ]);

        $conflicts = [];
        
        foreach ($request->dates as $requestedDate) {
            $requestedStart = $requestedDate['date'] . ' ' . $requestedDate['start_time'] . ':00';
            $requestedEnd = $requestedDate['date'] . ' ' . $requestedDate['end_time'] . ':00';
            
            // Buscar conflictos en reservaciones aprobadas
            $conflictingReservations = reservation_classroom::where('classroom_id', $request->classroom_id)
                ->where('status', 'Aprobado')
                ->where(function ($query) use ($requestedStart, $requestedEnd, $requestedDate) {
                    // Conflictos con fechas regulares (start_datetime y end_datetime)
                    $query->where(function ($q) use ($requestedStart, $requestedEnd) {
                        $q->where(function ($subQ) use ($requestedStart, $requestedEnd) {
                            $subQ->where('start_datetime', '<', $requestedEnd)
                                 ->where('end_datetime', '>', $requestedStart);
                        });
                    })
                    // También verificar en irregular_dates
                    ->orWhereRaw("JSON_EXTRACT(irregular_dates, '$[*].date') LIKE ?", ['%' . $requestedDate['date'] . '%']);
                })
                ->get();

            foreach ($conflictingReservations as $reservation) {
                // Verificar conflictos detallados
                $hasConflict = false;
                $conflictDetails = [];

                // Verificar irregular_dates si existen
                if (!is_null($reservation->irregular_dates)) {
                    $irregularDates = is_string($reservation->irregular_dates) 
                        ? json_decode($reservation->irregular_dates, true) 
                        : $reservation->irregular_dates;
                    
                    if (is_array($irregularDates)) {
                        foreach ($irregularDates as $date) {
                            if ($date['date'] === $requestedDate['date']) {
                                $existingStart = strtotime($requestedDate['date'] . ' ' . $date['startTime']);
                                $existingEnd = strtotime($requestedDate['date'] . ' ' . $date['endTime']);
                                $newStart = strtotime($requestedStart);
                                $newEnd = strtotime($requestedEnd);
                                
                                if (($newStart < $existingEnd) && ($newEnd > $existingStart)) {
                                    $hasConflict = true;
                                    $conflictDetails = [
                                        'existing_start' => $date['startTime'],
                                        'existing_end' => $date['endTime'],
                                        'conflict_type' => 'irregular_date'
                                    ];
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    // Verificar con start_datetime y end_datetime regulares
                    $existingStart = strtotime($reservation->start_datetime);
                    $existingEnd = strtotime($reservation->end_datetime);
                    $newStart = strtotime($requestedStart);
                    $newEnd = strtotime($requestedEnd);
                    
                    if (($newStart < $existingEnd) && ($newEnd > $existingStart)) {
                        $hasConflict = true;
                        $conflictDetails = [
                            'existing_start' => \Carbon\Carbon::parse($reservation->start_datetime)->format('H:i'),
                            'existing_end' => \Carbon\Carbon::parse($reservation->end_datetime)->format('H:i'),
                            'conflict_type' => 'regular_date'
                        ];
                    }
                }

                if ($hasConflict) {
                    $conflicts[] = [
                        'date' => $requestedDate['date'],
                        'requested_start' => $requestedDate['start_time'],
                        'requested_end' => $requestedDate['end_time'],
                        'existing_reservation' => [
                            'id' => $reservation->id,
                            'title' => $reservation->event_title,
                            'organizer' => $reservation->full_name,
                            'start_time' => $conflictDetails['existing_start'],
                            'end_time' => $conflictDetails['existing_end'],
                        ]
                    ];
                }
            }
        }

        return response()->json([
            'conflicts' => $conflicts,
            'has_conflicts' => !empty($conflicts)
        ]);
    }
public function store(Request $request)
{
    // Validar los datos del formulario
    $validated = $request->validate([
        'user_id' => 'nullable|exists:users,id',
        'full_name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'required|string|max:255',
        'faculty_id' => 'required|exists:faculties,id',
        'municipality_id' => 'required|exists:municipality,id',
        'classroom_id' => 'required|exists:classrooms,id',
        'event_title' => 'required|string|max:255',
        'category_type' => 'required|exists:categories,id',
        'attendees' => 'required|integer|min:1',
        'start_datetime' => 'required|date_format:Y-m-d H:i:s',
        'end_datetime' => 'required|date_format:Y-m-d H:i:s|after:start_datetime',
        'requirements' => 'nullable|string',
        'status' => 'required|in:Pendiente',
        'is_recurring' => 'required|boolean',
        'repeticion' => 'nullable|integer|min:1',
        'recurring_frequency' => 'nullable|in:daily,weekly,monthly',
        'recurring_days' => 'nullable', // Cambiar a nullable sin especificar tipo
        'recurring_end_date' => 'nullable|date|after_or_equal:today',
        'irregular_dates' => 'nullable', // Cambiar a nullable sin especificar tipo
        'cost' => 'required|numeric|min:0',
        'is_paid' => 'required|boolean',
        'payment_date' => 'nullable|date',
    ]);

    // Procesar recurring_days - preparar array limpio para el modelo
    $cleanDays = null;
    if (!empty($validated['recurring_days'])) {        
        // Si recurring_days viene como array (caso actual según el log)
        if (is_array($validated['recurring_days'])) {
            $rawDays = $validated['recurring_days'];
        }
        // Si recurring_days viene como string JSON
        elseif (is_string($validated['recurring_days'])) {
            try {
                $decoded = json_decode($validated['recurring_days'], true);
                if (is_array($decoded)) {
                    $rawDays = $decoded;
                } else {
                    $rawDays = [$validated['recurring_days']];
                }
            } catch (Exception $e) {
                $rawDays = [$validated['recurring_days']];
            }
        }
        
        // Validar y limpiar días válidos
        if (!empty($rawDays)) {
            $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            $filteredDays = array_filter($rawDays, function($day) use ($validDays) {
                return in_array($day, $validDays);
            });
            $cleanDays = array_values($filteredDays); // Reindexar como array simple
        }
    }

    // Procesar irregular_dates de manera similar
    $fechas_irregulares = null;
    if (!empty($validated['irregular_dates'])) {
        // Si irregular_dates viene como string JSON
        if (is_string($validated['irregular_dates'])) {
            try {
                $decoded = json_decode($validated['irregular_dates'], true);
                if (is_array($decoded)) {
                    $fechas_irregulares = $decoded;
                }
            } catch (Exception $e) {
                $fechas_irregulares = null;
            }
        }
        // Si irregular_dates viene como array
        elseif (is_array($validated['irregular_dates'])) {
            $fechas_irregulares = $validated['irregular_dates'];
        }
    }

    // Preparar los datos para la creación
    $data = [
        'user_id' => $validated['user_id'] ?? null,
        'full_name' => $validated['full_name'],
        'Email' => $validated['email'],
        'phone' => $validated['phone'],
        'faculty_id' => $validated['faculty_id'],
        'municipality_id' => $validated['municipality_id'],
        'classroom_id' => $validated['classroom_id'],
        'event_title' => $validated['event_title'],
        'category_type' => $validated['category_type'],
        'attendees' => $validated['attendees'],
        'start_datetime' => $validated['start_datetime'],
        'end_datetime' => $validated['end_datetime'],
        'requirements' => $validated['requirements'] ?? '',
        'status' => $validated['status'],
        'is_recurring' => $validated['is_recurring'],
        'repeticion' => $validated['repeticion'],
        'recurring_frequency' => $validated['recurring_frequency'],
        'recurring_days' => $cleanDays, // Enviar array directamente, Eloquent lo convierte a JSON
        'recurring_end_date' => $validated['recurring_end_date'],
        'irregular_dates' => $fechas_irregulares ? json_encode($fechas_irregulares) : null, // Usar la variable procesada
        'cost' => $validated['cost'],
        'is_paid' => $validated['is_paid'],
        'payment_date' => $validated['payment_date'],
    ];

    // Debug: Log para verificar los datos procesados (opcional, remover en producción)
    \Log::info('Datos procesados para recurring_days:');
    \Log::info('Original: ' . print_r($validated['recurring_days'] ?? 'null', true));
    \Log::info('Array limpio enviado a Eloquent: ' . print_r($cleanDays ?? 'null', true));

    // Crear la reservación
    $reservation = reservation_classroom::create($data);

    // Redirigir con mensaje de éxito
    return redirect()->route('home')
        ->with('success', 'Reserva creada correctamente. En espera de aprobación.');
}
}