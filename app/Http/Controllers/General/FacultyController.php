<?php

namespace App\Http\Controllers\General;

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
use Illuminate\Support\Facades\Log;

class FacultyController extends Controller
{
    public function index(Request $request)
    {
        $municipalityId = $request->query('municipality_id', 1);
        $municipalities = Municipality::all(['id', 'name']);
        $users = User::whereIn('rol_id', [2, 3])->get(['id', 'name', 'rol_id']);

        // Cargar todas las facultades sin filtrar por municipality_id
        $faculties = faculty::with(['classrooms', 'responsibleUser'])->get();

        return Inertia::render('Admin/General/Faculties/Index', [
            'faculties' => $faculties,
            'municipalities' => $municipalities,
            'selectedMunicipality' => $municipalityId,
            'users' => $users,
        ]);
    }

    // Los métodos create, store, edit, update y destroy permanecen sin cambios
    public function create()
    {
        $municipalities = Municipality::all();
        return Inertia::render('Admin/General/Faculties/Create', [
            'municipalities' => $municipalities,
            'users' => User::whereIn('rol_id', [2, 3])->get(['id', 'name', 'rol_id']) ?? [],
        ]);
    }

public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'location' => 'required|string|max:255',
        'responsible' => 'nullable|exists:users,id',
        'email' => 'nullable|email|max:255',
        'phone' => 'nullable|string|max:50',
        'municipality_id' => 'required|exists:municipality,id',
        'services' => 'required|string',
        'description' => 'required|string',
        'web_site' => 'nullable|url|max:255',
        'capacity' => 'nullable|integer|min:1',
        'image_option' => 'required|in:url,upload',
        'image_url' => 'nullable|url|max:255|required_if:image_option,url',
        'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048|required_if:image_option,upload',
        'classrooms' => 'array',
        'classrooms.*.name' => 'required|string|max:255',
        'classrooms.*.capacity' => 'required|integer|min:1',
        'classrooms.*.services' => 'required|string',
        'classrooms.*.responsible' => 'nullable|exists:users,id',
        'classrooms.*.email' => 'nullable|email|max:255',
        'classrooms.*.phone' => 'nullable|string|max:50',
        'classrooms.*.image_option' => 'required|in:url,upload',
        'classrooms.*.image_url' => 'nullable|url|max:255|required_if:classrooms.*.image_option,url',
        'classrooms.*.image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048|required_if:classrooms.*.image_option,upload',
        'classrooms.*.uses_db_storage' => 'required|boolean',
    ]);

    $data = $request->only([
        'name',
        'location',
        'responsible',
        'email',
        'phone',
        'municipality_id',
        'services',
        'description',
        'web_site',
        'capacity',
    ]);

    $data['capacity'] = $request->input('capacity', "0");

    if ($request->image_option === 'upload' && $request->hasFile('image_file')) {
        $path = $request->file('image_file')->store('faculties', 'public');
        $data['image'] = $path;
    } elseif ($request->image_option === 'url') {
        $data['image'] = $request->image_url;
    } else {
        $data['image'] = null;
    }

    // Recolectar todos los responsables que se van a asignar
    $responsablesToAssign = [];
    
    // Responsable de la facultad
    if ($request->responsible) {
        $responsablesToAssign[] = $request->responsible;
    }
    
    // Responsables de classrooms
    if ($request->has('classrooms') && is_array($request->classrooms)) {
        foreach ($request->classrooms as $classroomData) {
            if (isset($classroomData['responsible']) && $classroomData['responsible']) {
                $responsablesToAssign[] = $classroomData['responsible'];
            }
        }
    }
    
    // Eliminar duplicados del array
    $responsablesToAssign = array_unique($responsablesToAssign);
    
    // Limpiar responsables duplicados usando Eloquent (esto actualizará automáticamente los timestamps)
    if (!empty($responsablesToAssign)) {
        foreach ($responsablesToAssign as $responsableId) {
            try {
                // Limpiar responsables duplicados en faculties usando Eloquent
                faculty::where('responsible', $responsableId)
                    ->update(['responsible' => null]);
                
                // Limpiar responsables duplicados en classrooms usando Eloquent
                classroom::where('responsible', $responsableId)
                    ->update(['responsible' => null]);
                
                // Limpiar asignaciones previas en users usando Eloquent
                User::where('id', $responsableId)
                    ->whereIn('rol_id', [2, 3])
                    ->update(['responsible_id' => null]);
                    
            } catch (\Exception $e) {
                \Log::error('Error en limpieza de responsable ID: ' . $responsableId . ' - ' . $e->getMessage());
            }
        }
    }

    $faculty = faculty::create($data);

    // Actualizar tabla users para el responsable de la facultad creada usando Eloquent
    if ($request->responsible) {
        try {
            User::where('id', $request->responsible)
                ->where('rol_id', 3)
                ->update(['responsible_id' => $faculty->id]);
        } catch (\Exception $e) {
            \Log::error('Error al asignar responsable de facultad: ' . $e->getMessage());
        }
    }

    // Procesar classrooms si existen
    if ($request->has('classrooms') && is_array($request->classrooms)) {
        foreach ($request->classrooms as $classroomData) {
            $classroom = [
                'name' => $classroomData['name'],
                'capacity' => $classroomData['capacity'],
                'services' => $classroomData['services'],
                'responsible' => $classroomData['responsible'] ?? null,
                'email' => $classroomData['email'] ?? null,
                'phone' => $classroomData['phone'] ?? null,
                'uses_db_storage' => $classroomData['uses_db_storage'],
            ];

            if ($classroomData['image_option'] === 'upload' && isset($classroomData['image_file'])) {
                $classroom['image_url'] = null;
                $classroom['image_path'] = $classroomData['image_file']->store('classrooms', 'public');
            } elseif ($classroomData['image_option'] === 'url') {
                $classroom['image_url'] = $classroomData['image_url'] ?? null;
                $classroom['image_path'] = null;
            } else {
                $classroom['image_url'] = null;
                $classroom['image_path'] = null;
            }

            $createdClassroom = $faculty->classrooms()->create($classroom);

            // Actualizar tabla users para el responsable del classroom creado usando Eloquent
            if (isset($classroomData['responsible']) && $classroomData['responsible']) {
                try {
                    User::where('id', $classroomData['responsible'])
                        ->where('rol_id', 2)
                        ->update(['responsible_id' => $createdClassroom->id]);
                } catch (\Exception $e) {
                    \Log::error('Error al asignar responsable de classroom: ' . $e->getMessage());
                }
            }
        }
    }

    return redirect()->route('admin.general.faculties.index')->with('success', 'Facultad creada con éxito.');
}

    public function edit(faculty $faculty)
    {
        $faculty->load('classrooms');
        $municipalities = Municipality::all();
        $users = User::whereIn('rol_id', [2, 3])->get(['id', 'name', 'rol_id']);

        return Inertia::render('Admin/General/Faculties/Edit', [
            'faculty' => $faculty,
            'municipalities' => $municipalities,
            'users' => $users,
        ]);
    }

public function update(Request $request, faculty $faculty)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'location' => 'required|string|max:255',
        'responsible' => 'nullable|exists:users,id',
        'email' => 'nullable|email|max:255',
        'phone' => 'nullable|string|max:50',
        'municipality_id' => 'required|exists:municipality,id',
        'services' => 'required|string',
        'description' => 'required|string',
        'web_site' => 'nullable|url|max:255',
        'capacity' => 'nullable|integer|min:1',
        'image_option' => 'required|in:url,upload',
        'image_url' => 'nullable|url|max:255|required_if:image_option,url',
        'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048|required_if:image_option,upload',
        'classrooms' => 'nullable|array',
        'classrooms.*.id' => 'nullable|exists:classrooms,id',
        'classrooms.*.name' => 'required|string|max:255',
        'classrooms.*.capacity' => 'required|integer|min:1',
        'classrooms.*.services' => 'required|string',
        'classrooms.*.responsible' => 'nullable|exists:users,id',
        'classrooms.*.email' => 'nullable|email|max:255',
        'classrooms.*.phone' => 'nullable|string|max:50',
        'classrooms.*.image_option' => 'required|in:url,upload',
        'classrooms.*.image_url' => 'nullable|url|max:255|required_if:classrooms.*.image_option,url',
        'classrooms.*.image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048|required_if:classrooms.*.image_option,upload',
        'classrooms.*.uses_db_storage' => 'required|boolean',
    ]);

    $data = $request->only([
        'name',
        'location',
        'responsible',
        'email',
        'phone',
        'municipality_id',
        'services',
        'description',
        'web_site',
        'capacity',
    ]);

    $data['capacity'] = $request->input('capacity', "0");

    if ($request->image_option === 'upload' && $request->hasFile('image_file')) {
        if ($faculty->image && !filter_var($faculty->image, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($faculty->image);
        }
        $data['image'] = $request->file('image_file')->store('faculties', 'public');
    } elseif ($request->image_option === 'url') {
        if ($faculty->image && !filter_var($faculty->image, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($faculty->image);
        }
        $data['image'] = $request->image_url;
    }

    // LIMPIEZA DE RESPONSABLES DUPLICADOS - FACULTAD
    $responsablesToAssign = [];
    
    if ($request->responsible) {
        $responsablesToAssign[] = $request->responsible;
    }
    
    if ($request->has('classrooms') && is_array($request->classrooms)) {
        foreach ($request->classrooms as $classroomData) {
            if (isset($classroomData['responsible']) && $classroomData['responsible']) {
                $responsablesToAssign[] = $classroomData['responsible'];
            }
        }
    }
    
    $responsablesToAssign = array_unique($responsablesToAssign);
    
    if (!empty($responsablesToAssign)) {
        foreach ($responsablesToAssign as $responsableId) {
            try {
                $facultyQuery = faculty::where('responsible', $responsableId);
                if ($request->responsible == $responsableId) {
                    $facultyQuery->where('id', '!=', $faculty->id);
                }
                $facultyQuery->update(['responsible' => null]);
                
                $classroomQuery = classroom::where('responsible', $responsableId);
                
                if ($request->has('classrooms') && is_array($request->classrooms)) {
                    $classroomsToExclude = [];
                    foreach ($request->classrooms as $classroomData) {
                        if (isset($classroomData['responsible']) && $classroomData['responsible'] == $responsableId && isset($classroomData['id'])) {
                            $classroomsToExclude[] = $classroomData['id'];
                        }
                    }
                    if (!empty($classroomsToExclude)) {
                        $classroomQuery->whereNotIn('id', $classroomsToExclude);
                    }
                }
                $classroomQuery->update(['responsible' => null]);
                
                User::where('id', $responsableId)
                    ->whereIn('rol_id', [2, 3])
                    ->update(['responsible_id' => null]);
                    
            } catch (\Exception $e) {
                \Log::error('Error en limpieza de responsable ID: ' . $responsableId . ' - ' . $e->getMessage());
            }
        }
    }

    $faculty->update($data);

    if ($request->responsible) {
        try {
            User::where('responsible_id', $faculty->id)
                ->where('rol_id', 3)
                ->update(['responsible_id' => null]);
            
            User::where('id', $request->responsible)
                ->where('rol_id', 3)
                ->update(['responsible_id' => $faculty->id]);
        } catch (\Exception $e) {
            \Log::error('Error al asignar responsable de facultad: ' . $e->getMessage());
        }
    } else {
        User::where('responsible_id', $faculty->id)
            ->where('rol_id', 3)
            ->update(['responsible_id' => null]);
    }

    // MANEJO DE CLASSROOMS CON LIMPIEZA MEJORADA
    $existingClassroomIds = $faculty->classrooms->pluck('id')->toArray();
    
    // Mejorada lógica para manejar arrays vacíos (del segundo código)
    $submittedClassroomIds = $request->has('classrooms') ? collect($request->input('classrooms'))->pluck('id')->filter()->map(fn($id) => (int)$id)->toArray() : [];
    
    $classroomsToDelete = array_diff($existingClassroomIds, $submittedClassroomIds);
    
    // Debug logging para verificar qué classrooms se van a eliminar
    \Log::info('=== CLASSROOM DELETION DEBUG ===');
    \Log::info('Request has classrooms: ' . ($request->has('classrooms') ? 'YES' : 'NO'));
    \Log::info('Request classrooms value: ' . json_encode($request->classrooms));
    \Log::info('Existing classroom IDs: ' . json_encode($existingClassroomIds));
    \Log::info('Submitted classroom IDs (after conversion): ' . json_encode($submittedClassroomIds));
    \Log::info('Classrooms to delete: ' . json_encode($classroomsToDelete));
    \Log::info('=== END DEBUG ===');
    
    // LIMPIEZA COMPLETA DE CLASSROOMS (del primer código)
    foreach ($classroomsToDelete as $classroomId) {
        $classroom = classroom::find($classroomId);
        if ($classroom) {
            try {
                \Log::info("Eliminando classroom ID: {$classroomId} - {$classroom->name}");
                
                // 1. Limpiar usuarios asociados al classroom que se va a eliminar (tabla users)
                $affectedUsers = User::where('responsible_id', $classroomId)
                    ->where('rol_id', 2)
                    ->update(['responsible_id' => null]);
                \Log::info("Limpiados {$affectedUsers} usuarios en tabla users para classroom ID: {$classroomId}");
                
                // 2. Limpiar referencias en otras tablas donde este classroom sea responsable (tabla classrooms)
                if ($classroom->responsible) {
                    // Limpiar si el responsable de este classroom es responsable de otros classrooms
                    $otherClassrooms = classroom::where('responsible', $classroom->responsible)
                        ->where('id', '!=', $classroomId)
                        ->update(['responsible' => null]);
                    \Log::info("Limpiadas {$otherClassrooms} referencias de responsable en otras classrooms");
                    
                    // Limpiar si el responsable de este classroom es responsable de alguna facultad
                    $facultiesAffected = faculty::where('responsible', $classroom->responsible)
                        ->update(['responsible' => null]);
                    \Log::info("Limpiadas {$facultiesAffected} referencias de responsable en facultades");
                }
                
                // 3. Eliminar reservaciones y quejas asociadas
                reservation_classroom::where('classroom_id', $classroomId)->delete();
                complaint_classroom::where('classroom_id', $classroomId)->delete();
                
                // 4. Eliminar imagen si existe
                if ($classroom->image_path && Storage::disk('public')->exists($classroom->image_path)) {
                    Storage::disk('public')->delete($classroom->image_path);
                }
                
                // 5. Finalmente eliminar el classroom
                $classroom->delete();
                \Log::info("Classroom ID {$classroomId} eliminado exitosamente");
                
            } catch (\Exception $e) {
                \Log::error("Error eliminando classroom ID {$classroomId}: " . $e->getMessage());
            }
        }
    }

    // PROCESAMIENTO DE CLASSROOMS (mejorado del segundo código)
    if ($request->has('classrooms')) {
        foreach ($request->classrooms as $classroomData) {
            $classroom = [
                'name' => $classroomData['name'],
                'capacity' => $classroomData['capacity'],
                'services' => $classroomData['services'],
                'responsible' => $classroomData['responsible'],
                'email' => $classroomData['email'],
                'phone' => $classroomData['phone'],
                'uses_db_storage' => $classroomData['uses_db_storage'],
            ];

            if ($classroomData['image_option'] === 'upload' && isset($classroomData['image_file'])) {
                if (isset($classroomData['id'])) {
                    $existingClassroom = classroom::find($classroomData['id']);
                    if ($existingClassroom && $existingClassroom->image_path) {
                        Storage::disk('public')->delete($existingClassroom->image_path);
                    }
                }
                $classroom['image_url'] = null;
                $classroom['image_path'] = $classroomData['image_file']->store('classrooms', 'public');
            } elseif ($classroomData['image_option'] === 'url') {
                if (isset($classroomData['id'])) {
                    $existingClassroom = classroom::find($classroomData['id']);
                    if ($existingClassroom && $existingClassroom->image_path) {
                        Storage::disk('public')->delete($existingClassroom->image_path);
                    }
                }
                $classroom['image_url'] = $classroomData['image_url'];
                $classroom['image_path'] = null;
            }

            $classroomId = null;
            if (isset($classroomData['id']) && $classroomData['id']) {
                classroom::where('id', $classroomData['id'])->update($classroom);
                $classroomId = $classroomData['id'];
            } else {
                $newClassroom = $faculty->classrooms()->create($classroom);
                $classroomId = $newClassroom->id;
            }

            if (isset($classroomData['responsible']) && $classroomData['responsible']) {
                try {
                    // LIMPIEZA ADICIONAL: Limpiar cualquier classroom que tenga este mismo responsable
                    // antes de asignarlo al classroom actual
                    classroom::where('responsible', $classroomData['responsible'])
                        ->where('id', '!=', $classroomId)
                        ->update(['responsible' => null]);
                    
                    // Limpiar cualquier facultad que tenga este mismo responsable
                    faculty::where('responsible', $classroomData['responsible'])
                        ->update(['responsible' => null]);
                    
                    // Limpiar usuarios asociados al classroom actual
                    User::where('responsible_id', $classroomId)
                        ->where('rol_id', 2)
                        ->update(['responsible_id' => null]);
                    
                    // Limpiar usuarios con este responsable que tengan rol_id 2 (solo classrooms)
                    User::where('id', $classroomData['responsible'])
                        ->where('rol_id', 2)
                        ->update(['responsible_id' => null]);
                    
                    // Finalmente asignar el nuevo responsable al classroom actual
                    User::where('id', $classroomData['responsible'])
                        ->where('rol_id', 2)
                        ->update(['responsible_id' => $classroomId]);
                        
                    \Log::info("Responsable ID {$classroomData['responsible']} asignado correctamente al classroom ID {$classroomId}");
                } catch (\Exception $e) {
                    \Log::error('Error al asignar responsable de classroom: ' . $e->getMessage());
                }
            } else {
                User::where('responsible_id', $classroomId)
                    ->where('rol_id', 2)
                    ->update(['responsible_id' => null]);
            }
        }
    }

    return redirect()->route('admin.general.faculties.index')
        ->with('success', 'Facultad actualizada con éxito.');
}

public function destroy(faculty $faculty)
{
    // LIMPIEZA PREVIA: Limpiar responsabilidades antes de eliminar la facultad y sus classrooms
    try {
        // 1. Limpiar usuarios que tienen esta facultad como responsible_id (rol_id = 3 - Administrador estatal)
        User::where('responsible_id', $faculty->id)
            ->where('rol_id', 3)
            ->update(['responsible_id' => null]);
        
        \Log::info("Limpiados usuarios con responsible_id de facultad ID: {$faculty->id}");
    } catch (\Exception $e) {
        \Log::error('Error limpiando usuarios de facultad: ' . $e->getMessage());
    }

    // 2. Procesar cada classroom individualmente
    foreach ($faculty->classrooms as $classroom) {
        try {
            // Limpiar usuarios que tienen este classroom como responsible_id (rol_id = 2 - Administrador área)
            User::where('responsible_id', $classroom->id)
                ->where('rol_id', 2)
                ->update(['responsible_id' => null]);
            
            \Log::info("Limpiados usuarios con responsible_id de classroom ID: {$classroom->id}");
        } catch (\Exception $e) {
            \Log::error('Error limpiando usuarios de classroom ID ' . $classroom->id . ': ' . $e->getMessage());
        }

        // Eliminar reservaciones y quejas asociadas al classroom
        try {
            reservation_classroom::where('classroom_id', $classroom->id)->delete();
            complaint_classroom::where('classroom_id', $classroom->id)->delete();
        } catch (\Exception $e) {
            \Log::error('Error eliminando reservaciones/quejas del classroom ID ' . $classroom->id . ': ' . $e->getMessage());
        }

        // Eliminar imagen del classroom si existe
        try {
            if ($classroom->image_path && Storage::disk('public')->exists($classroom->image_path)) {
                Storage::disk('public')->delete($classroom->image_path);
            }
        } catch (\Exception $e) {
            \Log::error('Error eliminando imagen del classroom ID ' . $classroom->id . ': ' . $e->getMessage());
        }

        // Eliminar el classroom
        try {
            $classroom->delete();
        } catch (\Exception $e) {
            \Log::error('Error eliminando classroom ID ' . $classroom->id . ': ' . $e->getMessage());
        }
    }

    // LIMPIEZA ADICIONAL: Verificar y limpiar cualquier referencia residual
    try {
        // Limpiar cualquier referencia del responsable de la facultad en otras tablas (por si hay inconsistencias)
        if ($faculty->responsible) {
            // Verificar si el responsable de esta facultad es responsable de otras facultades
            faculty::where('responsible', $faculty->responsible)
                ->where('id', '!=', $faculty->id)
                ->update(['responsible' => null]);

            // Verificar si el responsable de esta facultad es responsable de algún classroom
            classroom::where('responsible', $faculty->responsible)
                ->update(['responsible' => null]);

            \Log::info("Limpiadas referencias adicionales del responsable ID: {$faculty->responsible}");
        }

        // Limpiar referencias de responsables de classrooms que ya no existen
        $classroomResponsibles = $faculty->classrooms->pluck('responsible')->filter()->unique();
        foreach ($classroomResponsibles as $responsibleId) {
            // Limpiar si este responsable está asignado a otros classrooms
            classroom::where('responsible', $responsibleId)
                ->whereNotIn('id', $faculty->classrooms->pluck('id'))
                ->update(['responsible' => null]);

            // Limpiar si este responsable está asignado a alguna facultad
            faculty::where('responsible', $responsibleId)
                ->where('id', '!=', $faculty->id)
                ->update(['responsible' => null]);

            \Log::info("Limpiadas referencias adicionales del responsable de classroom ID: {$responsibleId}");
        }

    } catch (\Exception $e) {
        \Log::error('Error en limpieza adicional de referencias: ' . $e->getMessage());
    }

    // Eliminar imagen de la facultad si existe
    try {
        if ($faculty->image && !str_starts_with($faculty->image, 'http') && Storage::disk('public')->exists($faculty->image)) {
            Storage::disk('public')->delete($faculty->image);
        }
    } catch (\Exception $e) {
        \Log::error('Error eliminando imagen de la facultad ID ' . $faculty->id . ': ' . $e->getMessage());
    }

    // Finalmente eliminar la facultad
    try {
        $faculty->delete();
        \Log::info("Facultad ID {$faculty->id} eliminada exitosamente");
    } catch (\Exception $e) {
        \Log::error('Error eliminando facultad ID ' . $faculty->id . ': ' . $e->getMessage());
        return redirect()->route('admin.general.faculties.index')
            ->with('error', 'Error al eliminar la facultad.');
    }

    return redirect()->route('admin.general.faculties.index')
        ->with('success', 'Facultad eliminada con éxito.');
}
}