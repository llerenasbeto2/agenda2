<?php

namespace App\Http\Controllers\Estatal;
use App\Http\Controllers\Controller;
use App\Models\faculty;
use App\Models\classroom;
use App\Models\Municipality;
use App\Models\complaint_classroom;
use App\Models\reservation_classroom;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EstatalFacultyController extends Controller
{
   
   
public function index(Request $request)
{
    $user = Auth::user();
    $municipality = Municipality::all(['id', 'name']);
    $users = User::whereIn('rol_id', [2, 3])->get(['id', 'name', 'rol_id']);

    $faculty = null;
    if ($user->rol_id === 3 && $user->responsible_id) {
        $faculty = faculty::where('responsible', $user->id)
            ->where('id', $user->responsible_id) // Asegurar que la facultad coincida con responsible_id
            ->with(['classrooms' => function ($query) {
                // AGREGADOS: email, phone y cargar relación con usuario responsable
                $query->select('id', 'name', 'faculty_id', 'responsible', 'capacity', 'services', 'email', 'phone', 'image_url', 'image_path')
                      ->with(['responsibleUser:id,name']); // Cargar la relación del usuario responsable
            }, 'responsibleUser'])
            ->first();
    } elseif ($user->rol_id === 2 && $user->faculty_id) {
        $faculty = faculty::where('id', $user->faculty_id)
            ->with(['classrooms' => function ($query) {
                // AGREGADOS: email, phone y cargar relación con usuario responsable
                $query->select('id', 'name', 'faculty_id', 'responsible', 'capacity', 'services', 'email', 'phone', 'image_url', 'image_path')
                      ->with(['responsibleUser:id,name']); // Cargar la relación del usuario responsable
            }, 'responsibleUser'])
            ->first();
    }

    if (!$faculty) {
        return Inertia::render('Admin/Estatal/Faculties/Index', [
            'faculties' => [],
            'municipality' => $municipality,
            'selectedMunicipality' => null,
            'users' => $users,
            'error' => 'No tienes una facultad asignada.'
        ]);
    }

    $classrooms = $faculty->classrooms;
    if ($user->rol_id === 2) {
        $classrooms = $classrooms->where('responsible', $user->id);
    }

    // Depurar las aulas
    Log::info('Usuario ID: ' . $user->id . ', Facultad ID: ' . $faculty->id . ', Nombre: ' . $faculty->name);
    Log::info('Aulas cargadas: ', $classrooms->map(function ($classroom) {
        return [
            'id' => $classroom->id,
            'name' => $classroom->name,
            'faculty_id' => $classroom->faculty_id,
            'email' => $classroom->email,
            'phone' => $classroom->phone,
            'responsible' => $classroom->responsible,
            'responsible_user' => $classroom->responsibleUser ? $classroom->responsibleUser->name : null,
            'services' => $classroom->services
        ];
    })->toArray());

    return Inertia::render('Admin/Estatal/Faculties/Index', [
        'faculties' => [$faculty],
        'classrooms' => $classrooms->values(),
        'municipality' => $municipality,
        'selectedMunicipality' => $faculty->municipality_id,
        'users' => $users,
    ]);
}

    public function storeClassroom(Request $request)
    {
        $user = Auth::user();
        $faculty = null;

        if ($user->rol_id === 3 && $user->responsible_id) {
            $faculty = faculty::find($user->responsible_id);
        } elseif ($user->rol_id === 2 && $user->faculty_id) {
            $faculty = faculty::find($user->faculty_id);
        }

        if (!$faculty) {
            return redirect()->route('admin.estatal.faculties.index')
                ->with('error', 'No tienes una facultad asignada para crear aulas.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'services' => 'required|string',
            'responsible' => 'nullable|exists:users,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'image_option' => 'required|in:url,upload',
            'image_url' => 'nullable|url|max:255|required_if:image_option,url',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048|required_if:image_option,upload',
            'uses_db_storage' => 'required|boolean',
        ]);

        $classroom = [
            'faculty_id' => $faculty->id,
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'services' => $validated['services'],
            'responsible' => $validated['responsible'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'uses_db_storage' => $validated['uses_db_storage'],
        ];

        if ($validated['image_option'] === 'upload' && $request->hasFile('image_file')) {
            $classroom['image_url'] = null;
            $classroom['image_path'] = $request->file('image_file')->store('classrooms', 'public');
        } elseif ($validated['image_option'] === 'url') {
            $classroom['image_url'] = $validated['image_url'];
            $classroom['image_path'] = null;
        } else {
            $classroom['image_url'] = null;
            $classroom['image_path'] = null;
        }

        classroom::create($classroom);

        return redirect()->route('admin.estatal.faculties.index')
            ->with('success', 'Aula creada con éxito.');
    }

public function createClassroom()
{
    $user = Auth::user();
    $faculty = null;

    // Determinar la facultad según el rol del usuario
    if ($user->rol_id === 3 && $user->responsible_id) {
        $faculty = faculty::find($user->responsible_id);
    } elseif ($user->rol_id === 2 && $user->faculty_id) {
        $faculty = faculty::find($user->faculty_id);
    }

    // Verificar que el usuario tenga una facultad asignada
    if (!$faculty) {
        return redirect()->route('admin.estatal.faculties.index')
            ->with('error', 'No tienes una facultad asignada para crear aulas.');
    }

    // Obtener datos necesarios para el formulario
    $municipality = Municipality::all(['id', 'name']);
    $users = User::whereIn('rol_id', [2, 3])->get(['id', 'name', 'rol_id']);

    return Inertia::render('Admin/Estatal/Faculties/Create', [
        'faculty' => $faculty,
        'municipality' => $municipality,
        'users' => $users,
    ]);
}
    public function editClassroom(classroom $classroom)
    {
        $user = Auth::user();
        $faculty = null;

        if ($user->rol_id === 3 && $user->responsible_id) {
            $faculty = faculty::find($user->responsible_id);
        } elseif ($user->rol_id === 2 && $user->faculty_id) {
            $faculty = faculty::find($user->faculty_id);
        }

        if (!$faculty || $classroom->faculty_id !== $faculty->id || ($user->rol_id === 2 && $classroom->responsible !== $user->id)) {
            return redirect()->route('admin.estatal.faculties.index')
                ->with('error', 'No tienes permisos para editar esta aula.');
        }

        $municipality = Municipality::all();
        $users = User::whereIn('rol_id', [2, 3])->get(['id', 'name', 'rol_id']);

        return Inertia::render('Admin/Estatal/Faculties/Edit', [
            'classroom' => $classroom,
            'faculty' => $faculty,
            'municipality' => $municipality,
            'users' => $users,
        ]);
    }

    public function updateClassroom(Request $request, classroom $classroom)
    {
        $user = Auth::user();
        $faculty = null;

        if ($user->rol_id === 3 && $user->responsible_id) {
            $faculty = Faculty::find($user->responsible_id);
        } elseif ($user->rol_id === 2 && $user->faculty_id) {
            $faculty = Faculty::find($user->faculty_id);
        }

        if (!$faculty || $classroom->faculty_id !== $faculty->id || ($user->rol_id === 2 && $classroom->responsible !== $user->id)) {
            return redirect()->route('admin.estatal.faculties.index')
                ->with('error', 'No tienes permisos para editar esta aula.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'services' => 'required|string',
            'responsible' => 'nullable|exists:users,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'image_option' => 'required|in:url,upload',
            'image_url' => 'nullable|url|max:255|required_if:image_option,url',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048|required_if:image_option,upload',
            'uses_db_storage' => 'required|boolean',
        ]);

        $classroom->fill([
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'services' => $validated['services'],
            'responsible' => $validated['responsible'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'uses_db_storage' => $validated['uses_db_storage'],
        ]);

        if ($validated['image_option'] === 'upload' && $request->hasFile('image_file')) {
            if ($classroom->image_path && Storage::disk('public')->exists($classroom->image_path)) {
                Storage::disk('public')->delete($classroom->image_path);
            }
            $classroom->image_url = null;
            $classroom->image_path = $request->file('image_file')->store('classrooms', 'public');
        } elseif ($validated['image_option'] === 'url') {
            if ($classroom->image_path && Storage::disk('public')->exists($classroom->image_path)) {
                Storage::disk('public')->delete($classroom->image_path);
            }
            $classroom->image_url = $validated['image_url'];
            $classroom->image_path = null;
        }

        $classroom->save();

        return redirect()->route('admin.estatal.faculties.index')
            ->with('success', 'Aula actualizada con éxito.');
    }

    public function destroyClassroom(classroom $classroom)
    {
        $user = Auth::user();
        $faculty = null;

        if ($user->rol_id === 3 && $user->responsible_id) {
            $faculty = faculty::find($user->responsible_id);
        } elseif ($user->rol_id === 2 && $user->faculty_id) {
            $faculty = faculty::find($user->faculty_id);
        }

        if (!$faculty || $classroom->faculty_id !== $faculty->id || ($user->rol_id === 2 && $classroom->responsible !== $user->id)) {
            return redirect()->route('admin.estatal.faculties.index')
                ->with('error', 'No tienes permisos para eliminar esta aula.');
        }

        reservation_classroom::where('classroom_id', $classroom->id)->delete();
        complaint_classroom::where('classroom_id', $classroom->id)->delete();
        if ($classroom->image_path && Storage::disk('public')->exists($classroom->image_path)) {
            Storage::disk('public')->delete($classroom->image_path);
        }
        $classroom->delete();

        return redirect()->route('admin.estatal.faculties.index')
            ->with('success', 'Aula eliminada con éxito.');
    }
}
