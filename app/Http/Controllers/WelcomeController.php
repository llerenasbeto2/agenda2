<?php

namespace App\Http\Controllers;
use App\Models\Faculty;
use App\Models\Municipality;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\reservation_classroom;
use Illuminate\Foundation\Application;
use Carbon\Carbon;

class WelcomeController extends Controller
{
    public function index()
    {
        $faculties = Faculty::withCount('classrooms')->get();
        $municipalities = Municipality::get();
        $classrooms = Classroom::get();
      

        // Fetch approved reservations with related models
        $reservations = reservation_classroom::where('status', 'Aprobado')
            ->with(['classroom.faculty', 'faculty.municipality'])
            ->get();

        $events = [];
        $maxIterations = 1000; // Safeguard to prevent infinite loops

        foreach ($reservations as $reservation) {
            $iterationCount = 0;

            // Prepare common event data structure with all necessary IDs
            $commonEventData = [
                'title' => $reservation->event_title,
                'classroom' => $reservation->classroom ? $reservation->classroom->name : 'N/A',
                'faculty' => $reservation->faculty ? $reservation->faculty->name : 'N/A',
                'faculty_id' => $reservation->faculty_id,
                'classroom_id' => $reservation->classroom_id,
                // Get municipality_id from faculty relationship
                'municipality_id' => $reservation->faculty && $reservation->faculty->municipality 
                    ? $reservation->faculty->municipality_id 
                    : null,
                'municipality' => $reservation->faculty && $reservation->faculty->municipality 
                    ? $reservation->faculty->municipality->name 
                    : 'N/A',
            ];

            if ($reservation->is_recurring && $reservation->repeticion && $reservation->recurring_frequency) {
                // Handle recurring events
                $startDate = Carbon::parse($reservation->start_datetime);
                $endDate = $reservation->recurring_end_date ? Carbon::parse($reservation->recurring_end_date) : $startDate->copy()->addMonths(6);
                $days = $reservation->recurring_days ?? [];

                if ($reservation->recurring_frequency === 'weekly') {
                    $currentDate = $startDate->copy();
                    $maxWeeks = min($reservation->repeticion, 100); // Cap weeks
                    $weekCount = 0;

                    // Check if start date's day matches one of the recurring days
                    $startDayOfWeek = $startDate->englishDayOfWeek;
                    if (in_array($startDayOfWeek, $days)) {
                        $events[] = array_merge($commonEventData, [
                            'date' => $startDate->format('Y-m-d'),
                            'start_time' => $startDate->format('H:i'),
                            'end_time' => Carbon::parse($reservation->end_datetime)->format('H:i'),
                        ]);
                        $iterationCount++;
                    }

                    while ($weekCount < $maxWeeks && $currentDate <= $endDate && $iterationCount < $maxIterations) {
                        foreach ($days as $day) {
                            $nextDate = $currentDate->copy()->next($day);
                            if ($nextDate > $endDate || $nextDate < $startDate) continue;
                            $events[] = array_merge($commonEventData, [
                                'date' => $nextDate->format('Y-m-d'),
                                'start_time' => $startDate->format('H:i'),
                                'end_time' => Carbon::parse($reservation->end_datetime)->format('H:i'),
                            ]);
                            $iterationCount++;
                        }
                        $currentDate->addWeek();
                        $weekCount++;
                    }
                } elseif ($reservation->recurring_frequency === 'monthly') {
                    // Parse start and end dates
                    $startDate = Carbon::parse($reservation->start_datetime);
                    
                    // Validate repeticion
                    if (!isset($reservation->repeticion) || $reservation->repeticion <= 0) {
                        \Log::warning('Invalid or missing repeticion value, skipping event generation.', [
                            'reservation_id' => $reservation->id ?? 'N/A',
                            'repeticion' => $reservation->repeticion
                        ]);
                        continue;
                    }
                    $maxMonths = min($reservation->repeticion, 100); // Cap at 100 months for safety

                    // Handle recurring_end_date: if null, use repeticion
                    $endDate = $reservation->recurring_end_date 
                        ? Carbon::parse($reservation->recurring_end_date)->endOfDay() 
                        : $startDate->copy()->addMonths($maxMonths)->endOfDay();

                    // Validate end_datetime
                    if (Carbon::parse($reservation->end_datetime)->lte($startDate)) {
                        \Log::warning('Invalid end_datetime, must be after start_datetime.', [
                            'reservation_id' => $reservation->id ?? 'N/A',
                            'start_datetime' => $startDate->toDateTimeString(),
                            'end_datetime' => $reservation->end_datetime
                        ]);
                        continue;
                    }

                    // Handle recurring_days: support JSON string, array, or invalid input
                    $days = [];
                    if (is_string($reservation->recurring_days)) {
                        $decoded = json_decode($reservation->recurring_days, true);
                        $days = is_array($decoded) ? $decoded : [];
                    } elseif (is_array($reservation->recurring_days)) {
                        $days = $reservation->recurring_days;
                    }

                    // Validate days to ensure they are valid English day names
                    $validDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    $days = array_filter($days, fn($day) => in_array($day, $validDays));
                    $days = array_values($days); // Reindex array

                    // Skip if no valid days
                    if (empty($days)) {
                        \Log::warning('No valid recurring days provided, skipping event generation.', [
                            'reservation_id' => $reservation->id ?? 'N/A',
                            'recurring_days' => $reservation->recurring_days
                        ]);
                        continue;
                    }

                    // ... (resto del código de monthly - mantener igual pero agregar array_merge con $commonEventData)

                    // Calculate the week ordinal of the start date
                    $startDayOfWeek = $startDate->englishDayOfWeek;
                    $startDayIndex = ['Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6][$startDayOfWeek];
                    $firstDayOfStartMonth = $startDate->copy()->firstOfMonth();
                    $tempDate = $firstDayOfStartMonth->copy();
                    $weekOfMonth = 0;
                    $weekStart = $firstDayOfStartMonth->copy();
                    
                    while ($tempDate->lte($startDate)) {
                        if ($tempDate->dayOfWeek === 0) { // New week starts on Sunday
                            $weekOfMonth++;
                            $weekStart = $tempDate->copy();
                        }
                        if ($tempDate->isSameDay($startDate)) {
                            break;
                        }
                        $tempDate->addDay();
                    }

                    // Calculate the week ordinal of the end date
                    $endDayOfWeek = $endDate->englishDayOfWeek;
                    $endDayIndex = ['Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6][$endDayOfWeek];
                    $endMonthStart = $endDate->copy()->firstOfMonth();
                    $tempDate = $endMonthStart->copy();
                    $endWeekOfMonth = 0;
                    $endWeekStart = $endMonthStart->copy();
                    
                    while ($tempDate->lte($endDate)) {
                        if ($tempDate->dayOfWeek === 0) {
                            $endWeekOfMonth++;
                            $endWeekStart = $tempDate->copy();
                        }
                        if ($tempDate->isSameDay($endDate)) {
                            break;
                        }
                        $tempDate->addDay();
                    }

                    // Process all months
                    $currentDate = $startDate->copy()->firstOfMonth();
                    $monthCount = 0;
                    $iterationCount = 0;

                    while ($monthCount <= $maxMonths && $currentDate->startOfMonth()->lte($endDate) && $iterationCount < $maxIterations) {
                        $monthStart = $currentDate->copy()->firstOfMonth();
                        $monthEnd = $currentDate->copy()->endOfMonth();
                        $weekCount = 0;
                        $tempDate = $monthStart->copy();
                        $targetWeekStart = null;

                        $targetWeekOfMonth = $monthStart->isSameMonth($endDate) ? $endWeekOfMonth : $weekOfMonth;

                        while ($tempDate->lte($monthEnd)) {
                            if ($tempDate->dayOfWeek === 0) {
                                $weekCount++;
                                if ($weekCount === $targetWeekOfMonth) {
                                    $targetWeekStart = $tempDate->copy();
                                    break;
                                }
                            }
                            $tempDate->addDay();
                        }

                        if ($targetWeekStart) {
                            foreach ($days as $day) {
                                $dayIndex = ['Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6][$day];
                                $targetDate = $targetWeekStart->copy()->addDays($dayIndex);
                                
                                $minDate = ($monthStart->isSameMonth($startDate)) ? $startDate->copy()->startOfDay() : $monthStart;
                                
                                if ($targetDate->gte($minDate) && $targetDate->lte($monthEnd) && $targetDate->lte($endDate)) {
                                    $events[] = array_merge($commonEventData, [
                                        'date' => $targetDate->format('Y-m-d'),
                                        'start_time' => $startDate->format('H:i'),
                                        'end_time' => Carbon::parse($reservation->end_datetime)->format('H:i'),
                                    ]);
                                }
                            }
                        }

                        if ($monthStart->isSameMonth($endDate) && in_array($endDate->englishDayOfWeek, $days)) {
                            $alreadyIncluded = false;
                            foreach ($events as $event) {
                                if ($event['date'] === $endDate->format('Y-m-d')) {
                                    $alreadyIncluded = true;
                                    break;
                                }
                            }
                            if (!$alreadyIncluded) {
                                $events[] = array_merge($commonEventData, [
                                    'date' => $endDate->format('Y-m-d'),
                                    'start_time' => $startDate->format('H:i'),
                                    'end_time' => Carbon::parse($reservation->end_datetime)->format('H:i'),
                                ]);
                            }
                        }

                        $currentDate->addMonthNoOverflow();
                        $monthCount++;
                        $iterationCount++;
                    }
                }
            } elseif ($reservation->irregular_dates) {
                // Handle irregular dates
                foreach ($reservation->irregular_dates as $irregular) {
                    if ($iterationCount >= $maxIterations) break;
                    $events[] = array_merge($commonEventData, [
                        'date' => $irregular['date'],
                        'start_time' => $irregular['startTime'],
                        'end_time' => $irregular['endTime'],
                    ]);
                    $iterationCount++;
                }
            } else {
                // Single event
                if ($iterationCount >= $maxIterations) continue;
                $events[] = array_merge($commonEventData, [
                    'date' => Carbon::parse($reservation->start_datetime)->format('Y-m-d'),
                    'start_time' => Carbon::parse($reservation->start_datetime)->format('H:i'),
                    'end_time' => Carbon::parse($reservation->end_datetime)->format('H:i'),
                ]);
                $iterationCount++;
            }
        }

        return Inertia::render('Welcome', [
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'faculties' => $faculties,
            'municipalities' => $municipalities,  
            'classrooms' => $classrooms,
            'events' => $events,
        ]);
    }
}