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
use Illuminate\Support\Facades\DB;

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
        $faculty = Faculty::find($user->responsible_id);
    } elseif ($user->rol_id === 2 && $user->faculty_id) {
        $faculty = Faculty::find($user->faculty_id);
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

    DB::transaction(function () use ($validated, $faculty, $request) {
        // Limpiar responsables duplicados si se va a asignar uno
        if ($validated['responsible']) {
            try {
                // Limpiar responsables duplicados en faculties usando Eloquent
                Faculty::where('responsible', $validated['responsible'])
                    ->update(['responsible' => null]);
                
                // Limpiar responsables duplicados en classrooms usando Eloquent
                Classroom::where('responsible', $validated['responsible'])
                    ->update(['responsible' => null]);
                
                // Limpiar asignaciones previas en users usando Eloquent
                User::where('id', $validated['responsible'])
                    ->whereIn('rol_id', [2, 3])
                    ->update(['responsible_id' => null]);
                    
            } catch (\Exception $e) {
                \Log::error('Error en limpieza de responsable ID: ' . $validated['responsible'] . ' - ' . $e->getMessage());
            }
        }

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

        $createdClassroom = Classroom::create($classroom);

        // Actualizar tabla users para el responsable del classroom creado usando Eloquent
        if ($validated['responsible']) {
            try {
                User::where('id', $validated['responsible'])
                    ->where('rol_id', 2)
                    ->update(['responsible_id' => $createdClassroom->id]);
            } catch (\Exception $e) {
                \Log::error('Error al asignar responsable de classroom: ' . $e->getMessage());
            }
        }
    });

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

   public function updateClassroom(Request $request, Classroom $classroom)
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

    DB::transaction(function () use ($validated, $classroom, $request) {
        // LIMPIEZA DE RESPONSABLES DUPLICADOS (solo si se va a asignar un responsable)
        if ($validated['responsible']) {
            try {
                // Limpiar responsables duplicados en faculties usando Eloquent
                Faculty::where('responsible', $validated['responsible'])
                    ->update(['responsible' => null]);
                
                // Limpiar responsables duplicados en classrooms usando Eloquent 
                // (excluyendo el classroom actual que estamos actualizando)
                Classroom::where('responsible', $validated['responsible'])
                    ->where('id', '!=', $classroom->id)
                    ->update(['responsible' => null]);
                
                // Limpiar asignaciones previas en users usando Eloquent
                User::where('id', $validated['responsible'])
                    ->whereIn('rol_id', [2, 3])
                    ->update(['responsible_id' => null]);
                    
            } catch (\Exception $e) {
                \Log::error('Error en limpieza de responsable ID: ' . $validated['responsible'] . ' - ' . $e->getMessage());
            }
        }

        // Actualizar los datos del classroom
        $classroom->fill([
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'services' => $validated['services'],
            'responsible' => $validated['responsible'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'uses_db_storage' => $validated['uses_db_storage'],
        ]);

        // Manejar la imagen
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

        // ACTUALIZACIÓN DE RESPONSABILIDADES EN TABLA USERS
        if ($validated['responsible']) {
            try {
                // Limpiar usuarios asociados al classroom actual
                User::where('responsible_id', $classroom->id)
                    ->where('rol_id', 2)
                    ->update(['responsible_id' => null]);
                
                // Asignar el nuevo responsable al classroom actual
                User::where('id', $validated['responsible'])
                    ->where('rol_id', 2)
                    ->update(['responsible_id' => $classroom->id]);
                    
                \Log::info("Responsable ID {$validated['responsible']} asignado correctamente al classroom ID {$classroom->id}");
            } catch (\Exception $e) {
                \Log::error('Error al asignar responsable de classroom: ' . $e->getMessage());
            }
        } else {
            // Si no hay responsable, limpiar cualquier usuario asociado a este classroom
            User::where('responsible_id', $classroom->id)
                ->where('rol_id', 2)
                ->update(['responsible_id' => null]);
        }
    });

    return redirect()->route('admin.estatal.faculties.index')
        ->with('success', 'Aula actualizada con éxito.');
}

public function destroyClassroom(Classroom $classroom)
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
            ->with('error', 'No tienes permisos para eliminar esta aula.');
    }

    DB::transaction(function () use ($classroom) {
        // LIMPIEZA PREVIA: Limpiar responsabilidades antes de eliminar el classroom
        try {
            // 1. Limpiar usuarios que tienen este classroom como responsible_id (rol_id = 2 - Administrador área)
            $affectedUsers = User::where('responsible_id', $classroom->id)
                ->where('rol_id', 2)
                ->update(['responsible_id' => null]);
            \Log::info("Limpiados {$affectedUsers} usuarios con responsible_id de classroom ID: {$classroom->id}");
            
        } catch (\Exception $e) {
            \Log::error('Error limpiando usuarios de classroom ID ' . $classroom->id . ': ' . $e->getMessage());
        }

        // LIMPIEZA ADICIONAL: Verificar y limpiar cualquier referencia residual del responsable
        if ($classroom->responsible) {
            try {
                // 2. Limpiar si el responsable de este classroom es responsable de otros classrooms
                $otherClassrooms = Classroom::where('responsible', $classroom->responsible)
                    ->where('id', '!=', $classroom->id)
                    ->update(['responsible' => null]);
                \Log::info("Limpiadas {$otherClassrooms} referencias de responsable en otros classrooms");
                
                // 3. Limpiar si el responsable de este classroom es responsable de alguna facultad
                $facultiesAffected = Faculty::where('responsible', $classroom->responsible)
                    ->update(['responsible' => null]);
                \Log::info("Limpiadas {$facultiesAffected} referencias de responsable en facultades");
                
                // 4. Limpiar cualquier asignación residual del responsable en la tabla users
                User::where('id', $classroom->responsible)
                    ->whereIn('rol_id', [2, 3])
                    ->update(['responsible_id' => null]);
                \Log::info("Limpiada asignación residual del responsable ID: {$classroom->responsible}");
                
            } catch (\Exception $e) {
                \Log::error('Error en limpieza adicional del responsable ID ' . $classroom->responsible . ': ' . $e->getMessage());
            }
        }

        // 5. Eliminar reservaciones asociadas al classroom
        try {
            $deletedReservations = Reservation_classroom::where('classroom_id', $classroom->id)->delete();
            \Log::info("Eliminadas {$deletedReservations} reservaciones del classroom ID: {$classroom->id}");
        } catch (\Exception $e) {
            \Log::error('Error eliminando reservaciones del classroom ID ' . $classroom->id . ': ' . $e->getMessage());
        }

        // 6. Eliminar quejas asociadas al classroom
        try {
            $deletedComplaints = Complaint_classroom::where('classroom_id', $classroom->id)->delete();
            \Log::info("Eliminadas {$deletedComplaints} quejas del classroom ID: {$classroom->id}");
        } catch (\Exception $e) {
            \Log::error('Error eliminando quejas del classroom ID ' . $classroom->id . ': ' . $e->getMessage());
        }

        // 7. Eliminar imagen del classroom si existe
        try {
            if ($classroom->image_path && Storage::disk('public')->exists($classroom->image_path)) {
                Storage::disk('public')->delete($classroom->image_path);
                \Log::info("Imagen eliminada del classroom ID: {$classroom->id}");
            }
        } catch (\Exception $e) {
            \Log::error('Error eliminando imagen del classroom ID ' . $classroom->id . ': ' . $e->getMessage());
        }

        // 8. Finalmente eliminar el classroom
        try {
            $classroomId = $classroom->id;
            $classroomName = $classroom->name;
            $classroom->delete();
            \Log::info("Classroom ID {$classroomId} - {$classroomName} eliminado exitosamente");
        } catch (\Exception $e) {
            \Log::error('Error eliminando classroom ID ' . $classroom->id . ': ' . $e->getMessage());
            throw $e; // Re-lanzar la excepción para que la transacción se revierta
        }
    });

    return redirect()->route('admin.estatal.faculties.index')
        ->with('success', 'Aula eliminada con éxito.');
}

    /**
     * NUEVOS MÉTODOS AUXILIARES PARA MANEJAR SINCRONIZACIÓN
     */
    private function updateUserResponsibility($userId, $type, $entityId)
    {
        $user = User::find($userId);
        if ($user) {
            if ($type === 'classroom') {
                // Limpiar responsabilidades previas del usuario
                $this->clearUserResponsibility($userId, 'all');
                $user->update(['responsible_id' => $entityId]);
            }
        }
    }

    private function clearUserResponsibility($userId, $type = 'all')
    {
        $user = User::find($userId);
        if ($user) {
            $user->update(['responsible_id' => null]);
        }
    }
}