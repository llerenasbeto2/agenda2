<?php

namespace App\Http\Controllers\Users;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Categorie;
use App\Models\reservation_classroom;
use App\Models\RecurringPattern;
use App\Models\Classroom;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class UsersReservationsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userId = $user->id;
        $userEmail = $user->email;

        $reservations = reservation_classroom::with([
            'classroom', 
            'category'
        ])
        ->where(function ($query) use ($userId, $userEmail) {
            $query->where('user_id', $userId)
                  ->orWhere('Email', $userEmail);
        })
        ->orderBy('start_datetime', 'desc')
        ->get()
        ->unique('id'); // Elimina duplicados por ID de reservación

        return Inertia::render('MisReservaciones/Index', [
            'reservations_classrooms' => $reservations->values(), // Re-indexa la colección
        ]);
    }
}
