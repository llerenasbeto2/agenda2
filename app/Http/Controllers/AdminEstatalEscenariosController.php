<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\space_view;
use Inertia\Inertia;

class AdminEstatalEscenariosController extends Controller
{
    
    public function index()
    {
        $spaces = space_view::all();
        return Inertia::render('Admin/Estatal/EscenariosDisponibles/Index', [
            'posts' => $spaces
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Estatal/EscenariosDisponibles/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'location' => 'required',
            'capacity' => 'required|integer',
            'services' => 'required',
            'responsible' => 'required|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|max:50',
            'image' => 'required'
        ]);

        space_view::create($validated);

        return redirect()->route('admin.estatal.escenariosDisponibles.index');
    }

    public function edit(space_view $space)
    {
        return Inertia::render('Admin/Estatal/EscenariosDisponibles/Edit', [
            'space' => $space
        ]);
    }

    public function update(Request $request, space_view $space)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'location' => 'required',
            'capacity' => 'required|integer',
            'services' => 'required',
            'responsible' => 'required|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|max:50',
            'image' => 'required'
        ]);

        $space->update($validated);

        return redirect()->route('admin.estatal.escenariosDisponibles.index');
    }

    public function destroy(space_view $space)
    {
        $space->delete();
        return redirect()->route('admin.estatal.escenariosDisponibles.index');
    }
        
}
