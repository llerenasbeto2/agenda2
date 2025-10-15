<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Classroom;
use App\Models\Municipality;
use App\Models\Complaint_classroom;
use App\Models\Reservation_classroom;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class FacultyController extends Controller
{
    public function index(Request $request)
    {
        $municipalityId = $request->query('municipality_id', 1);
        $municipalities = Municipality::all(['id', 'name']);
        $users = User::whereIn('rol_id', [2, 3])->get(['id', 'name', 'rol_id']);

        // Cargar todas las facultades sin filtrar por municipality_id
        $faculties = Faculty::with(['classrooms', 'responsibleUser'])->get();

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

    DB::beginTransaction();
    
    try {
        // 1. Recolectar todos los responsables únicos
        $responsablesIds = $this->recolectarResponsables($request);
        
        // 2. Limpiar responsabilidades previas en batch
        if (!empty($responsablesIds)) {
            $this->limpiarResponsabilidadesPrevias($responsablesIds);
        }
        
        // 3. Preparar datos de la facultad
        $facultyData = $this->prepararDatosFacultad($request);
        
        // 4. Crear la facultad
        $faculty = Faculty::create($facultyData);
        
        // 5. Asignar responsable de la facultad
        if ($request->responsible) {
            User::where('id', $request->responsible)
                ->where('rol_id', 3)
                ->update(['responsible_id' => $faculty->id]);
        }
        
        // 6. Crear classrooms en batch
        if ($request->has('classrooms') && is_array($request->classrooms)) {
            $this->crearClassrooms($request->classrooms, $faculty);
        }
        
        DB::commit();
        
        \Log::info("Facultad ID {$faculty->id} creada con " . count($request->classrooms ?? []) . " aulas");
        
        return redirect()->route('admin.general.faculties.index')
            ->with('success', 'Facultad creada con éxito.');
            
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error al crear facultad: ' . $e->getMessage());
        
        return redirect()->back()
            ->withInput()
            ->with('error', 'Error al crear la facultad.');
    }
}

/**
 * Recolecta todos los IDs de responsables únicos
 */
private function recolectarResponsables(Request $request): array
{
    $responsables = [];
    
    if ($request->responsible) {
        $responsables[] = $request->responsible;
    }
    
    if ($request->has('classrooms') && is_array($request->classrooms)) {
        foreach ($request->classrooms as $classroom) {
            if (!empty($classroom['responsible'])) {
                $responsables[] = $classroom['responsible'];
            }
        }
    }
    
    return array_unique($responsables);
}

/**
 * Limpia responsabilidades previas en operaciones batch
 */
private function limpiarResponsabilidadesPrevias(array $responsablesIds): void
{
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
 * Prepara los datos de la facultad
 */
private function prepararDatosFacultad(Request $request): array
{
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
    
    // Procesar imagen
    if ($request->image_option === 'upload' && $request->hasFile('image_file')) {
        $data['image'] = $request->file('image_file')->store('faculties', 'public');
    } elseif ($request->image_option === 'url') {
        $data['image'] = $request->image_url;
    } else {
        $data['image'] = null;
    }
    
    return $data;
}

/**
 * Crea los classrooms y asigna sus responsables
 */
private function crearClassrooms(array $classroomsData, Faculty $faculty): void
{
    $responsablesToUpdate = [];
    
    foreach ($classroomsData as $classroomData) {
        $classroom = [
            'name' => $classroomData['name'],
            'capacity' => $classroomData['capacity'],
            'services' => $classroomData['services'],
            'responsible' => $classroomData['responsible'] ?? null,
            'email' => $classroomData['email'] ?? null,
            'phone' => $classroomData['phone'] ?? null,
            'uses_db_storage' => $classroomData['uses_db_storage'],
        ];
        
        // Procesar imagen del classroom
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
        
        // Preparar actualización de responsable
        if (!empty($classroomData['responsible'])) {
            $responsablesToUpdate[$classroomData['responsible']] = $createdClassroom->id;
        }
    }
    
    // Actualizar todos los responsables de classrooms en batch
    foreach ($responsablesToUpdate as $userId => $classroomId) {
        User::where('id', $userId)
            ->where('rol_id', 2)
            ->update(['responsible_id' => $classroomId]);
    }
}

    public function edit(Faculty $faculty)
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

public function update(Request $request, Faculty $faculty)
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

    DB::beginTransaction();
    
    try {
        // 1. Recolectar responsables y determinar cambios
        $responsablesIds = $this->recolectarResponsables($request);
        $submittedClassroomIds = $this->obtenerClassroomIdsEnviados($request);
        $classroomsToDelete = $this->determinarClassroomsAEliminar($faculty, $submittedClassroomIds);
        
        // 2. Limpiar responsabilidades previas (excluyendo asignaciones actuales)
        if (!empty($responsablesIds)) {
            $this->limpiarResponsabilidadesPreviasUpdate(
                $responsablesIds, 
                $faculty->id, 
                $submittedClassroomIds,
                $request->responsible
            );
        }
        
        // 3. Eliminar classrooms que ya no están en el request
        if (!empty($classroomsToDelete)) {
            $this->eliminarClassrooms($classroomsToDelete);
        }
        
        // 4. Actualizar datos de la facultad
        $facultyData = $this->prepararDatosFacultadUpdate($request, $faculty);
        $faculty->update($facultyData);
        
        // 5. Gestionar responsable de la facultad
        $this->gestionarResponsableFacultad($faculty->id, $request->responsible);
        
        // 6. Procesar classrooms (crear/actualizar)
        if ($request->has('classrooms')) {
            $this->procesarClassrooms($request->classrooms, $faculty);
        }
        
        DB::commit();
        
        \Log::info("Facultad ID {$faculty->id} actualizada exitosamente");
        
        return redirect()->route('admin.general.faculties.index')
            ->with('success', 'Facultad actualizada con éxito.');
            
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error("Error al actualizar facultad ID {$faculty->id}: " . $e->getMessage());
        
        return redirect()->back()
            ->withInput()
            ->with('error', 'Error al actualizar la facultad.');
    }
}

/**
 * Obtiene los IDs de classrooms enviados en el request
 */
private function obtenerClassroomIdsEnviados(Request $request): array
{
    if (!$request->has('classrooms')) {
        return [];
    }
    
    return collect($request->input('classrooms'))
        ->pluck('id')
        ->filter()
        ->map(fn($id) => (int)$id)
        ->toArray();
}

/**
 * Determina qué classrooms deben eliminarse
 */
private function determinarClassroomsAEliminar(Faculty $faculty, array $submittedIds): array
{
    $existingIds = $faculty->classrooms->pluck('id')->toArray();
    return array_diff($existingIds, $submittedIds);
}

/**
 * Limpia responsabilidades previas excluyendo las asignaciones actuales
 */
private function limpiarResponsabilidadesPreviasUpdate(
    array $responsablesIds, 
    int $facultyId, 
    array $classroomIdsExcluir,
    ?int $responsableFacultad
): void
{
    foreach ($responsablesIds as $responsableId) {
        // Limpiar faculties (excepto la actual si es su responsable)
        $facultyQuery = Faculty::where('responsible', $responsableId);
        if ($responsableId == $responsableFacultad) {
            $facultyQuery->where('id', '!=', $facultyId);
        }
        $facultyQuery->update(['responsible' => null]);
        
        // Limpiar classrooms (excepto los que se están actualizando con este responsable)
        $classroomQuery = Classroom::where('responsible', $responsableId);
        if (!empty($classroomIdsExcluir)) {
            $classroomQuery->whereNotIn('id', $classroomIdsExcluir);
        }
        $classroomQuery->update(['responsible' => null]);
        
        // Limpiar users
        User::where('id', $responsableId)
            ->whereIn('rol_id', [2, 3])
            ->update(['responsible_id' => null]);
    }
}

/**
 * Elimina classrooms y sus datos relacionados en batch
 */
private function eliminarClassrooms(array $classroomIds): void
{
    // 1. Obtener responsables antes de eliminar
    $responsables = Classroom::whereIn('id', $classroomIds)
        ->pluck('responsible')
        ->filter()
        ->unique()
        ->toArray();
    
    // 2. Limpiar usuarios asociados (batch)
    User::whereIn('responsible_id', $classroomIds)
        ->where('rol_id', 2)
        ->update(['responsible_id' => null]);
    
    // 3. Eliminar reservaciones y quejas (batch)
    Reservation_classroom::whereIn('classroom_id', $classroomIds)->delete();
    Complaint_classroom::whereIn('classroom_id', $classroomIds)->delete();
    
    // 4. Eliminar imágenes
    $classrooms = Classroom::whereIn('id', $classroomIds)->get();
    foreach ($classrooms as $classroom) {
        if ($classroom->image_path && Storage::disk('public')->exists($classroom->image_path)) {
            Storage::disk('public')->delete($classroom->image_path);
        }
    }
    
    // 5. Eliminar classrooms
    Classroom::whereIn('id', $classroomIds)->delete();
    
    \Log::info("Eliminados " . count($classroomIds) . " classrooms");
}

/**
 * Prepara los datos de la facultad para actualización
 */
private function prepararDatosFacultadUpdate(Request $request, Faculty $faculty): array
{
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
    
    // Procesar imagen
    if ($request->image_option === 'upload' && $request->hasFile('image_file')) {
        // Eliminar imagen anterior si no es URL
        if ($faculty->image && !filter_var($faculty->image, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($faculty->image);
        }
        $data['image'] = $request->file('image_file')->store('faculties', 'public');
    } elseif ($request->image_option === 'url') {
        // Eliminar imagen anterior si no es URL
        if ($faculty->image && !filter_var($faculty->image, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($faculty->image);
        }
        $data['image'] = $request->image_url;
    }
    
    return $data;
}

/**
 * Gestiona el responsable de la facultad
 */
private function gestionarResponsableFacultad(int $facultyId, ?int $responsableId): void
{
    if ($responsableId) {
        // Limpiar usuarios previos con esta facultad asignada
        User::where('responsible_id', $facultyId)
            ->where('rol_id', 3)
            ->update(['responsible_id' => null]);
        
        // Asignar nuevo responsable
        User::where('id', $responsableId)
            ->where('rol_id', 3)
            ->update(['responsible_id' => $facultyId]);
    } else {
        // Si no hay responsable, limpiar todos
        User::where('responsible_id', $facultyId)
            ->where('rol_id', 3)
            ->update(['responsible_id' => null]);
    }
}

/**
 * Procesa los classrooms (crear/actualizar)
 */
private function procesarClassrooms(array $classroomsData, Faculty $faculty): void
{
    foreach ($classroomsData as $classroomData) {
        $classroom = [
            'name' => $classroomData['name'],
            'capacity' => $classroomData['capacity'],
            'services' => $classroomData['services'],
            'responsible' => $classroomData['responsible'] ?? null,
            'email' => $classroomData['email'] ?? null,
            'phone' => $classroomData['phone'] ?? null,
            'uses_db_storage' => $classroomData['uses_db_storage'],
        ];
        
        // Procesar imagen
        $this->procesarImagenClassroom($classroomData, $classroom);
        
        // Crear o actualizar
        $classroomId = $this->crearOActualizarClassroom($classroomData, $classroom, $faculty);
        
        // Gestionar responsable
        $this->gestionarResponsableClassroom($classroomId, $classroomData['responsible'] ?? null);
    }
}

/**
 * Procesa la imagen del classroom
 */
private function procesarImagenClassroom(array $classroomData, array &$classroom): void
{
    if ($classroomData['image_option'] === 'upload' && isset($classroomData['image_file'])) {
        // Eliminar imagen anterior si existe
        if (isset($classroomData['id'])) {
            $existingClassroom = Classroom::find($classroomData['id']);
            if ($existingClassroom && $existingClassroom->image_path) {
                Storage::disk('public')->delete($existingClassroom->image_path);
            }
        }
        $classroom['image_url'] = null;
        $classroom['image_path'] = $classroomData['image_file']->store('classrooms', 'public');
    } elseif ($classroomData['image_option'] === 'url') {
        // Eliminar imagen anterior si existe
        if (isset($classroomData['id'])) {
            $existingClassroom = Classroom::find($classroomData['id']);
            if ($existingClassroom && $existingClassroom->image_path) {
                Storage::disk('public')->delete($existingClassroom->image_path);
            }
        }
        $classroom['image_url'] = $classroomData['image_url'] ?? null;
        $classroom['image_path'] = null;
    }
}

/**
 * Crea o actualiza un classroom
 */
private function crearOActualizarClassroom(array $classroomData, array $classroom, Faculty $faculty): int
{
    if (isset($classroomData['id']) && $classroomData['id']) {
        Classroom::where('id', $classroomData['id'])->update($classroom);
        return $classroomData['id'];
    } else {
        $newClassroom = $faculty->classrooms()->create($classroom);
        return $newClassroom->id;
    }
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

public function destroy(Faculty $faculty)
{
    DB::beginTransaction();
    
    try {
        // 1. Obtener IDs de classrooms antes de eliminarlos
        $classroomIds = $faculty->classrooms->pluck('id')->toArray();
        
        // 2. Limpiar usuarios vinculados a esta facultad (Administrador estatal)
        User::where('responsible_id', $faculty->id)
            ->where('rol_id', 3)
            ->update(['responsible_id' => null]);
        
        // 3. Limpiar usuarios vinculados a los classrooms (Administrador área)
        if (!empty($classroomIds)) {
            User::whereIn('responsible_id', $classroomIds)
                ->where('rol_id', 2)
                ->update(['responsible_id' => null]);
        }
        
        // 4. Eliminar reservaciones y quejas en una sola operación
        if (!empty($classroomIds)) {
            Reservation_classroom::whereIn('classroom_id', $classroomIds)->delete();
            Complaint_classroom::whereIn('classroom_id', $classroomIds)->delete();
        }
        
        // 5. Eliminar imágenes de classrooms
        $this->eliminarImagenesClassrooms($faculty->classrooms);
        
        // 6. Eliminar todos los classrooms
        if (!empty($classroomIds)) {
            Classroom::whereIn('id', $classroomIds)->delete();
        }
        
        // 7. Eliminar imagen de la facultad
        $this->eliminarImagenFacultad($faculty);
        
        // 8. Eliminar la facultad
        $faculty->delete();
        
        DB::commit();
        
        \Log::info("Facultad ID {$faculty->id} eliminada exitosamente con {$faculty->classrooms->count()} aulas");
        
        return redirect()->route('admin.general.faculties.index')
            ->with('success', 'Facultad eliminada con éxito.');
            
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error("Error al eliminar facultad ID {$faculty->id}: " . $e->getMessage());
        
        return redirect()->route('admin.general.faculties.index')
            ->with('error', 'Error al eliminar la facultad.');
    }
}

/**
 * Elimina las imágenes de los classrooms
 */
private function eliminarImagenesClassrooms($classrooms): void
{
    foreach ($classrooms as $classroom) {
        if ($classroom->image_path && Storage::disk('public')->exists($classroom->image_path)) {
            try {
                Storage::disk('public')->delete($classroom->image_path);
            } catch (\Exception $e) {
                \Log::warning("No se pudo eliminar imagen del classroom ID {$classroom->id}: " . $e->getMessage());
            }
        }
    }
}

/**
 * Elimina la imagen de la facultad
 */
private function eliminarImagenFacultad(Faculty $faculty): void
{
    if ($faculty->image && 
        !str_starts_with($faculty->image, 'http') && 
        Storage::disk('public')->exists($faculty->image)) {
        try {
            Storage::disk('public')->delete($faculty->image);
        } catch (\Exception $e) {
            \Log::warning("No se pudo eliminar imagen de la facultad ID {$faculty->id}: " . $e->getMessage());
        }
    }
}
}