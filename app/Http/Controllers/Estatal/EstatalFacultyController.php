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
    
    // 1. Obtener la facultad según el rol
    $faculty = $this->obtenerFacultadDelUsuario($user);
    
    if (!$faculty) {
        return redirect()->route('admin.estatal.faculties.index')
            ->with('error', 'No tienes una facultad asignada para crear aulas.');
    }
    
    // 2. Validar request
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
    
    DB::beginTransaction();
    
    try {
        // 3. Limpiar responsabilidades previas si se va a asignar uno
        if ($validated['responsible']) {
            $this->limpiarResponsabilidadesPrevias([$validated['responsible']]);
        }
        
        // 4. Preparar datos del classroom
        $classroomData = $this->prepararDatosClassroom($validated, $faculty->id, $request);
        
        // 5. Crear el classroom
        $classroom = Classroom::create($classroomData);
        
        // 6. Asignar responsable en tabla users
        if ($validated['responsible']) {
            User::where('id', $validated['responsible'])
                ->where('rol_id', 2)
                ->update(['responsible_id' => $classroom->id]);
        }
        
        DB::commit();
        
        \Log::info("Classroom ID {$classroom->id} creado exitosamente en facultad ID {$faculty->id}");
        
        return redirect()->route('admin.estatal.faculties.index')
            ->with('success', 'Aula creada con éxito.');
            
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error("Error al crear classroom: " . $e->getMessage());
        
        return redirect()->back()
            ->withInput()
            ->with('error', 'Error al crear el aula.');
    }
}

    private function limpiarResponsabilidadesPrevias(array $responsablesIds): void
{
    if (empty($responsablesIds)) {
        return;
    }
    
    // Limpiar en faculties (batch)
    Faculty::whereIn('responsible', $responsablesIds)
        ->update(['responsible' => null]);
    
    // Limpiar en classrooms (batch)
    Classroom::whereIn('responsible', $responsablesIds)
        ->update(['responsible' => null]);
    
    // Limpiar en users (batch)
    User::whereIn('id', $responsablesIds)
        ->whereIn('rol_id', [2, 3])
        ->update(['responsible_id' => null]);
}

/**
 * Obtiene la facultad según el rol del usuario
 */
private function obtenerFacultadDelUsuario(User $user): ?Faculty
{
    if ($user->rol_id === 3 && $user->responsible_id) {
        return Faculty::find($user->responsible_id);
    }
    
    if ($user->rol_id === 2 && $user->faculty_id) {
        return Faculty::find($user->faculty_id);
    }
    
    return null;
}

/**
 * Prepara los datos del classroom
 */
private function prepararDatosClassroom(array $validated, int $facultyId, Request $request): array
{
    $classroom = [
        'faculty_id' => $facultyId,
        'name' => $validated['name'],
        'capacity' => $validated['capacity'],
        'services' => $validated['services'],
        'responsible' => $validated['responsible'] ?? null,
        'email' => $validated['email'] ?? null,
        'phone' => $validated['phone'] ?? null,
        'uses_db_storage' => $validated['uses_db_storage'],
    ];
    
    // Procesar imagen
    if ($validated['image_option'] === 'upload' && $request->hasFile('image_file')) {
        $classroom['image_url'] = null;
        $classroom['image_path'] = $request->file('image_file')->store('classrooms', 'public');
    } elseif ($validated['image_option'] === 'url') {
        $classroom['image_url'] = $validated['image_url'] ?? null;
        $classroom['image_path'] = null;
    } else {
        $classroom['image_url'] = null;
        $classroom['image_path'] = null;
    }
    
    return $classroom;
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
    
    // 1. Verificar permisos
    if (!$this->verificarPermisosClassroom($user, $classroom)) {
        return redirect()->route('admin.estatal.faculties.index')
            ->with('error', 'No tienes permisos para editar esta aula.');
    }
    
    // 2. Validar request
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
    
    DB::beginTransaction();
    
    try {
        // 3. Limpiar responsabilidades previas si se va a asignar uno
        if ($validated['responsible']) {
            $this->limpiarResponsabilidadesPreviasUpdate(
                $validated['responsible'], 
                $classroom->id
            );
        }
        
        // 4. Actualizar datos básicos del classroom
        $classroom->fill([
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'services' => $validated['services'],
            'responsible' => $validated['responsible'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'uses_db_storage' => $validated['uses_db_storage'],
        ]);
        
        // 5. Procesar imagen
        $this->procesarImagenClassroom($validated, $classroom, $request);
        
        // 6. Guardar classroom
        $classroom->save();
        
        // 7. Gestionar responsable en tabla users
        $this->gestionarResponsableClassroom($classroom->id, $validated['responsible'] ?? null);
        
        DB::commit();
        
        \Log::info("Classroom ID {$classroom->id} actualizado exitosamente");
        
        return redirect()->route('admin.estatal.faculties.index')
            ->with('success', 'Aula actualizada con éxito.');
            
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error("Error al actualizar classroom ID {$classroom->id}: " . $e->getMessage());
        
        return redirect()->back()
            ->withInput()
            ->with('error', 'Error al actualizar el aula.');
    }
}

/**
 * Procesa la imagen del classroom
 */
private function procesarImagenClassroom(array $validated, Classroom $classroom, Request $request): void
{
    if ($validated['image_option'] === 'upload' && $request->hasFile('image_file')) {
        // Eliminar imagen anterior si existe
        if ($classroom->image_path && Storage::disk('public')->exists($classroom->image_path)) {
            Storage::disk('public')->delete($classroom->image_path);
        }
        $classroom->image_url = null;
        $classroom->image_path = $request->file('image_file')->store('classrooms', 'public');
    } elseif ($validated['image_option'] === 'url') {
        // Eliminar imagen anterior si existe
        if ($classroom->image_path && Storage::disk('public')->exists($classroom->image_path)) {
            Storage::disk('public')->delete($classroom->image_path);
        }
        $classroom->image_url = $validated['image_url'] ?? null;
        $classroom->image_path = null;
    }
}

/**
 * Verifica permisos para editar un classroom
 */
private function verificarPermisosClassroom(User $user, Classroom $classroom): bool
{
    $faculty = null;
    
    if ($user->rol_id === 3 && $user->responsible_id) {
        $faculty = Faculty::find($user->responsible_id);
    } elseif ($user->rol_id === 2 && $user->faculty_id) {
        $faculty = Faculty::find($user->faculty_id);
    }
    
    if (!$faculty || $classroom->faculty_id !== $faculty->id) {
        return false;
    }
    
    // Administrador area solo puede editar su propia aula
    if ($user->rol_id === 2 && $classroom->responsible !== $user->id) {
        return false;
    }
    
    return true;
}

/**
 * Limpia responsabilidades previas excluyendo el classroom actual
 */
private function limpiarResponsabilidadesPreviasUpdate(int $responsableId, int $classroomId): void
{
    // Limpiar faculties
    Faculty::where('responsible', $responsableId)
        ->update(['responsible' => null]);
    
    // Limpiar otros classrooms (excepto el actual)
    Classroom::where('responsible', $responsableId)
        ->where('id', '!=', $classroomId)
        ->update(['responsible' => null]);
    
    // Limpiar users
    User::where('id', $responsableId)
        ->whereIn('rol_id', [2, 3])
        ->update(['responsible_id' => null]);
}

/**
 * Gestiona el responsable de un classroom
 */
private function gestionarResponsableClassroom(int $classroomId, ?int $responsableId): void
{
    if ($responsableId) {
        // Limpiar usuarios previos con este classroom asignado
        User::where('responsible_id', $classroomId)
            ->where('rol_id', 2)
            ->update(['responsible_id' => null]);
        
        // Asignar nuevo responsable
        User::where('id', $responsableId)
            ->where('rol_id', 2)
            ->update(['responsible_id' => $classroomId]);
    } else {
        // Si no hay responsable, limpiar todos
        User::where('responsible_id', $classroomId)
            ->where('rol_id', 2)
            ->update(['responsible_id' => null]);
    }
}

public function destroyClassroom(Classroom $classroom)
{
    $user = Auth::user();
    
    // 1. Verificar permisos (reutiliza el método)
    if (!$this->verificarPermisosClassroom($user, $classroom)) {
        return redirect()->route('admin.estatal.faculties.index')
            ->with('error', 'No tienes permisos para eliminar esta aula.');
    }
    
    DB::beginTransaction();
    
    try {
        // 2. Limpiar usuarios asociados
        User::where('responsible_id', $classroom->id)
            ->where('rol_id', 2)
            ->update(['responsible_id' => null]);
        
        // 3. Eliminar reservaciones y quejas
        Reservation_classroom::where('classroom_id', $classroom->id)->delete();
        Complaint_classroom::where('classroom_id', $classroom->id)->delete();
        
        // 4. Eliminar imagen si existe
        if ($classroom->image_path && Storage::disk('public')->exists($classroom->image_path)) {
            Storage::disk('public')->delete($classroom->image_path);
        }
        
        // 5. Eliminar el classroom
        $classroom->delete();
        
        DB::commit();
        
        \Log::info("Classroom ID {$classroom->id} eliminado exitosamente");
        
        return redirect()->route('admin.estatal.faculties.index')
            ->with('success', 'Aula eliminada con éxito.');
            
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error("Error al eliminar classroom ID {$classroom->id}: " . $e->getMessage());
        
        return redirect()->back()
            ->with('error', 'Error al eliminar el aula.');
    }
}
}