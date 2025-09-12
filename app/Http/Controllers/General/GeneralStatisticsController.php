<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Municipality;
use App\Models\reservation_classroom;
use App\Models\classroom;
use App\Models\faculty;
use Inertia\Inertia;

class GeneralStatisticsController extends Controller
{
    //
    public function index(){
        return Inertia::render('Admin/General/Statistics/Index',[
            'municipality' => Municipality::all(),
            'faculties' => faculty::all(),
            'reservations_classrooms' => reservation_classroom::all(),
            'classrooms' => classroom::all(),
        ]);
    }
}
