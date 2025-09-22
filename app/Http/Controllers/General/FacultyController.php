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
        // Validación personalizada para la imagen
        $request->validate([
            'image_option' => 'required|in:url,upload',
        ]);

        // Validar que se proporcione imagen según la opción seleccionada
        if ($request->image_option === 'url') {
            $request->validate([
                'image_url' => 'required|url|max:255',
            ], [
                'image_url.required' => 'La URL de la imagen es obligatoria cuando seleccionas la opción URL.',
            ]);
        } elseif ($request->image_option === 'upload') {
            $request->validate([
                'image_file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ], [
                'image_file.required' => 'El archivo de imagen es obligatorio cuando seleccionas la opción subir archivo.',
            ]);
        }

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
            'image_url' => 'nullable|url|max:255',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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

        DB::transaction(function () use ($request, &$faculty) {
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

            $data['capacity'] = $request->input('capacity', null);

            // Procesar la imagen (ahora es obligatoria)
            if ($request->image_option === 'upload' && $request->hasFile('image_file')) {
                $path = $request->file('image_file')->store('faculties', 'public');
                $data['image'] = $path;
            } elseif ($request->image_option === 'url') {
                $data['image'] = $request->image_url;
            }

            $faculty = Faculty::create($data);

            // NUEVA LÓGICA: Actualizar tabla users si se asigna responsable de facultad
            if ($request->responsible) {
                $this->updateUserResponsibility($request->responsible, 'faculty', $faculty->id);
            }

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
                    $classroom['image_url'] = null;
                    $classroom['image_path'] = $classroomData['image_file']->store('classrooms', 'public');
                } elseif ($classroomData['image_option'] === 'url') {
                    $classroom['image_url'] = $classroomData['image_url'];
                    $classroom['image_path'] = null;
                } else {
                    $classroom['image_url'] = null;
                    $classroom['image_path'] = null;
                }

                $createdClassroom = $faculty->classrooms()->create($classroom);

                // NUEVA LÓGICA: Actualizar tabla users si se asigna responsable de classroom
                if ($classroomData['responsible']) {
                    $this->updateUserResponsibility($classroomData['responsible'], 'classroom', $createdClassroom->id);
                }
            }
        });

        return redirect()->route('admin.general.faculties.index')->with('success', 'Facultad creada con éxito.');
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

        DB::transaction(function () use ($request, $faculty) {
            // Obtener el responsable anterior de la facultad
            $oldFacultyResponsible = $faculty->responsible;

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

            $data['capacity'] = $request->input('capacity', null);

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

            $faculty->update($data);

            // NUEVA LÓGICA: Manejar cambios de responsable de facultad
            if ($oldFacultyResponsible != $request->responsible) {
                // Limpiar el responsable anterior
                if ($oldFacultyResponsible) {
                    $this->clearUserResponsibility($oldFacultyResponsible, 'faculty');
                }
                // Asignar nuevo responsable
                if ($request->responsible) {
                    $this->updateUserResponsibility($request->responsible, 'faculty', $faculty->id);
                }
            }

            if ($request->has('classrooms')) {
                $existingClassroomIds = $faculty->classrooms->pluck('id')->toArray();
                $submittedClassroomIds = collect($request->classrooms)->pluck('id')->filter()->toArray();

                $classroomsToDelete = array_diff($existingClassroomIds, $submittedClassroomIds);
                foreach ($classroomsToDelete as $classroomId) {
                    $classroom = Classroom::find($classroomId);
                    if ($classroom) {
                        // NUEVA LÓGICA: Limpiar responsabilidad antes de eliminar
                        if ($classroom->responsible) {
                            $this->clearUserResponsibility($classroom->responsible, 'classroom');
                        }

                        Reservation_classroom::where('classroom_id', $classroomId)->delete();
                        Complaint_classroom::where('classroom_id', $classroomId)->delete();
                        
                        if ($classroom->image_path && Storage::disk('public')->exists($classroom->image_path)) {
                            Storage::disk('public')->delete($classroom->image_path);
                        }
                        $classroom->delete();
                    }
                }

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
                            $existingClassroom = Classroom::find($classroomData['id']);
                            if ($existingClassroom && $existingClassroom->image_path) {
                                Storage::disk('public')->delete($existingClassroom->image_path);
                            }
                        }
                        $classroom['image_url'] = null;
                        $classroom['image_path'] = $classroomData['image_file']->store('classrooms', 'public');
                    } elseif ($classroomData['image_option'] === 'url') {
                        if (isset($classroomData['id'])) {
                            $existingClassroom = Classroom::find($classroomData['id']);
                            if ($existingClassroom && $existingClassroom->image_path) {
                                Storage::disk('public')->delete($existingClassroom->image_path);
                            }
                        }
                        $classroom['image_url'] = $classroomData['image_url'];
                        $classroom['image_path'] = null;
                    }

                    if (isset($classroomData['id']) && $classroomData['id']) {
                        // Actualizar classroom existente
                        $existingClassroom = Classroom::find($classroomData['id']);
                        $oldClassroomResponsible = $existingClassroom ? $existingClassroom->responsible : null;

                        Classroom::where('id', $classroomData['id'])->update($classroom);

                        // NUEVA LÓGICA: Manejar cambios de responsable
                        if ($oldClassroomResponsible != $classroomData['responsible']) {
                            // Limpiar el responsable anterior
                            if ($oldClassroomResponsible) {
                                $this->clearUserResponsibility($oldClassroomResponsible, 'classroom');
                            }
                            // Asignar nuevo responsable
                            if ($classroomData['responsible']) {
                                $this->updateUserResponsibility($classroomData['responsible'], 'classroom', $classroomData['id']);
                            }
                        }
                    } else {
                        // Crear nuevo classroom
                        $createdClassroom = $faculty->classrooms()->create($classroom);

                        // NUEVA LÓGICA: Asignar responsable si existe
                        if ($classroomData['responsible']) {
                            $this->updateUserResponsibility($classroomData['responsible'], 'classroom', $createdClassroom->id);
                        }
                    }
                }
            }
        });

        return redirect()->route('admin.general.faculties.index')
            ->with('success', 'Facultad actualizada con éxito.');
    }

    public function destroy(Faculty $faculty)
    {
        DB::transaction(function () use ($faculty) {
            foreach ($faculty->classrooms as $classroom) {
                // NUEVA LÓGICA: Limpiar responsabilidades antes de eliminar
                if ($classroom->responsible) {
                    $this->clearUserResponsibility($classroom->responsible, 'classroom');
                }

                Reservation_classroom::where('classroom_id', $classroom->id)->delete();
                Complaint_classroom::where('classroom_id', $classroom->id)->delete();
                if ($classroom->image_path && Storage::disk('public')->exists($classroom->image_path)) {
                    Storage::disk('public')->delete($classroom->image_path);
                }
                $classroom->delete();
            }

            // NUEVA LÓGICA: Limpiar responsabilidad de la facultad
            if ($faculty->responsible) {
                $this->clearUserResponsibility($faculty->responsible, 'faculty');
            }

            if ($faculty->image && !str_starts_with($faculty->image, 'http') && Storage::disk('public')->exists($faculty->image)) {
                Storage::disk('public')->delete($faculty->image);
            }
            $faculty->delete();
        });

        return redirect()->route('admin.general.faculties.index')->with('success', 'Facultad eliminada con éxito.');
    }

    /**
     * NUEVOS MÉTODOS AUXILIARES PARA MANEJAR SINCRONIZACIÓN
     */
    private function updateUserResponsibility($userId, $type, $entityId)
    {
        $user = User::find($userId);
        if ($user) {
            if ($type === 'faculty') {
                // Limpiar responsabilidades previas del usuario
                $this->clearUserResponsibility($userId, 'all');
                $user->update(['responsible_id' => $entityId]);
            } elseif ($type === 'classroom') {
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