<?php

namespace App\Http\Controllers;
use App\Models\classroom_statistic;
use Illuminate\Http\Request;
use Inertia\Inertia;
class StatisticController extends Controller
{
    //
    public function index(){
        return Inertia::render('Admin/General/Statistics/Index',[
            'classroom_statistics' => classroom_statistic::all(),
        ]);
    }
}
