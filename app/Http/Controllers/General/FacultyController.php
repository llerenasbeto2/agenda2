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
            // Solo URL de imagen ahora
            'image_url' => 'nullable|url|max:255',
            'classrooms' => 'array',
            'classrooms.*.name' => 'required|string|max:255',
            'classrooms.*.capacity' => 'required|integer|min:1',
            'classrooms.*.services' => 'required|string',
            'classrooms.*.responsible' => 'nullable|exists:users,id',
            'classrooms.*.email' => 'nullable|email|max:255',
            'classrooms.*.phone' => 'nullable|string|max:50',
            'classrooms.*.web_site' => 'nullable|url|max:255',
            // Solo URL de imagen para aulas
            'classrooms.*.image_url' => 'nullable|url|max:255',
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

        $data['capacity'] = $request->input('capacity', null);
        
        // Solo almacenar URL de imagen
        $data['image'] = $request->image_url;

        $faculty = faculty::create($data);

        foreach ($request->classrooms as $classroomData) {
            $classroom = [
                'name' => $classroomData['name'],
                'capacity' => $classroomData['capacity'],
                'services' => $classroomData['services'],
                'responsible' => $classroomData['responsible'],
                'email' => $classroomData['email'],
                'phone' => $classroomData['phone'],
                'web_site' => $classroomData['web_site'] ?? null,
                'uses_db_storage' => $classroomData['uses_db_storage'],
                // Solo URL de imagen
                'image_url' => $classroomData['image_url'] ?? null,
                'image_path' => null,
            ];

            $faculty->classrooms()->create($classroom);
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
            // Solo URL de imagen
            'image_url' => 'nullable|url|max:255',
            'classrooms' => 'nullable|array',
            'classrooms.*.id' => 'nullable|exists:classrooms,id',
            'classrooms.*.name' => 'required|string|max:255',
            'classrooms.*.capacity' => 'required|integer|min:1',
            'classrooms.*.services' => 'required|string',
            'classrooms.*.responsible' => 'nullable|exists:users,id',
            'classrooms.*.email' => 'nullable|email|max:255',
            'classrooms.*.phone' => 'nullable|string|max:50',
            'classrooms.*.web_site' => 'nullable|url|max:255',
            // Solo URL de imagen para aulas
            'classrooms.*.image_url' => 'nullable|url|max:255',
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

        $data['capacity'] = $request->input('capacity', null);
        
        // Solo almacenar URL de imagen
        $data['image'] = $request->image_url;

        $faculty->update($data);

        if ($request->has('classrooms')) {
            $existingClassroomIds = $faculty->classrooms->pluck('id')->toArray();
            $submittedClassroomIds = collect($request->classrooms)->pluck('id')->filter()->toArray();

            $classroomsToDelete = array_diff($existingClassroomIds, $submittedClassroomIds);
            foreach ($classroomsToDelete as $classroomId) {
                $classroom = Classroom::find($classroomId);
                if ($classroom) {
                    reservation_classroom::where('classroom_id', $classroomId)->delete();
                    complaint_classroom::where('classroom_id', $classroomId)->delete();
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
                    'web_site' => $classroomData['web_site'] ?? null,
                    'uses_db_storage' => $classroomData['uses_db_storage'],
                    // Solo URL de imagen
                    'image_url' => $classroomData['image_url'] ?? null,
                    'image_path' => null,
                ];

                if (isset($classroomData['id']) && $classroomData['id']) {
                    classroom::where('id', $classroomData['id'])->update($classroom);
                } else {
                    $faculty->classrooms()->create($classroom);
                }
            }
        }

        return redirect()->route('admin.general.faculties.index')
            ->with('success', 'Facultad actualizada con éxito.');
    }

    public function destroy(faculty $faculty)
    {
        foreach ($faculty->classrooms as $classroom) {
            reservation_classroom::where('classroom_id', $classroom->id)->delete();
            complaint_classroom::where('classroom_id', $classroom->id)->delete();
            $classroom->delete();
        }
        
        $faculty->delete();
        return redirect()->route('admin.general.faculties.index')->with('success', 'Facultad eliminada con éxito.');
    }
}