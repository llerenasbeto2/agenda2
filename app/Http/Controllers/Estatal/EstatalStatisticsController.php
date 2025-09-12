<?php

namespace App\Http\Controllers\Estatal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\classroom_statistic;
use App\Models\reservation_classroom;
use App\Models\classroom;
use App\Models\faculty;
use Inertia\Inertia;

class EstatalStatisticsController extends Controller
{
    //
    public function index(){
        return Inertia::render('Admin/Estatal/Statistics/Index',[
            'classroom_statistics' => classroom_statistic::all(),
            'faculties' => faculty::all(),
            'reservations_classrooms' => reservation_classroom::all(),
            'classrooms' => classroom::all(),
        ]);
    }
}
