<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\faculty;
use App\Models\Municipality;
use App\Models\classroom;
use Inertia\Inertia;


class AdminGeneralEscenariosController extends Controller
{
    //
    public function index()
    {
        $faculties = faculty::with('municipality')->get();

        return Inertia::render('Admin/General/EscenariosDisponibles/Index', [
            'posts' => $faculties
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/General/EscenariosDisponibles/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'location'        => 'required|string|max:255',
            'responsible'     => 'nullable|string|max:255',
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:50',
            'municipality_id' => 'nullable|exists:municipality,id',
            'capacity'        => 'required|integer',
            'services'        => 'required|string',
            'description'     => 'required|string',
            'image'           => 'nullable|url|max:255',
        ]);

        faculty::create($validated);

        return redirect()->route('admin.general.escenariosDisponibles.index');
    }

    public function edit(faculty $space)
    {
        $space->load('municipality');

        return Inertia::render('Admin/General/EscenariosDisponibles/Edit', [
            'space' => $space
        ]);
    }

    public function update(Request $request, faculty $space)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'location'        => 'required|string|max:255',
            'responsible'     => 'nullable|string|max:255',
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:50',
            'municipality_id' => 'nullable|exists:municipality,id',
            'capacity'        => 'required|integer',
            'services'        => 'required|string',
            'description'     => 'required|string',
            'image'           => 'nullable|url|max:255',
        ]);

        $space->update($validated);

        return redirect()->route('admin.general.escenariosDisponibles.index');
    }

    public function destroy(faculty $space)
    {
        // Delete all classrooms linked to this faculty
        classroom::where('faculty_id', $space->id)->delete();
        
        // Delete the faculty
        $space->delete();

        return redirect()->route('admin.general.escenariosDisponibles.index');
    }

    public function getSpaces()
    {
        $faculties = faculty::with('municipality')->get();
        return response()->json($faculties);
    }
}