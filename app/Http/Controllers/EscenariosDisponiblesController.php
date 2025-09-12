<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\faculty;
use App\Models\classroom;
use App\Models\municipality;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class EscenariosDisponiblesController extends Controller
{
    public function index()
    {
        $faculties = faculty::with(['classrooms', 'classrooms.responsibleUser'])
            ->orderBy('name')
            ->get()
            ->map(function ($faculty) {
                $facultyData = [
                    'id' => $faculty->id,
                    'name' => $faculty->name,
                    'location' => $faculty->location,
                    'capacity' => $faculty->capacity,
                    'services' => $faculty->services,
                    'description' => $faculty->description,
                    'web_site' => $faculty->web_site,
                    'phone' => $faculty->phone,
                    'email' => $faculty->email,
                    'image' => $this->getImageUrl($faculty->image, 'faculties'),
                    'municipality_id' => $faculty->municipality_id,
                    'classrooms' => $faculty->classrooms->map(function ($classroom) {
                        return [
                            'id' => $classroom->id,
                            'name' => $classroom->name,
                            'capacity' => $classroom->capacity,
                            'services' => $classroom->services,
                            'responsible' => $classroom->responsibleUser ? $classroom->responsibleUser->name : 'No asignado',
                            'email' => $classroom->email,
                            'phone' => $classroom->phone,
                            'image' => $this->getClassroomImageUrl($classroom),
                            'faculty_name' => $classroom->faculty->name,
                        ];
                    })->toArray(),
                ];
                \Log::info('Faculty Data: ', ['faculty' => $facultyData]);
                return $facultyData;
            });

        $municipalities = municipality::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(function ($municipality) {
                return [
                    'id' => $municipality->id,
                    'name' => $municipality->name,
                ];
            });

        return Inertia::render('EscenariosDisponibles/Index', [
            'faculties' => $faculties,
            'municipalities' => $municipalities,
        ]);
    }

    private function getImageUrl($image, $folder)
    {
        if (!$image) {
            return null;
        }
        return filter_var($image, FILTER_VALIDATE_URL)
            ? $image
            : Storage::url("{$folder}/{$image}");
    }

    private function getClassroomImageUrl($classroom)
    {
        if ($classroom->image_url && $classroom->image_url !== '') {
            \Log::info('Using image_url for classroom: ', ['id' => $classroom->id, 'image_url' => $classroom->image_url]);
            return $classroom->image_url;
        } elseif ($classroom->image_path && $classroom->image_path !== '') {
            $url = Storage::url($classroom->image_path);
            \Log::info('Using image_path for classroom: ', ['id' => $classroom->id, 'image_path' => $classroom->image_path, 'url' => $url]);
            return $url;
        }
        \Log::info('No image available for classroom: ', ['id' => $classroom->id]);
        return null;
    }

    public function getClassroomsByFaculty($facultyId)
    {
        $classrooms = classroom::with(['faculty', 'responsibleUser'])
            ->where('faculty_id', $facultyId)
            ->orderBy('name')
            ->get()
            ->map(function ($classroom) {
                $classroomData = [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                    'capacity' => $classroom->capacity,
                    'services' => $classroom->services,
                    'responsible' => $classroom->responsibleUser ? $classroom->responsibleUser->name : 'No asignado',
                    'email' => $classroom->email,
                    'phone' => $classroom->phone,
                    'image' => $this->getClassroomImageUrl($classroom),
                    'faculty_name' => $classroom->faculty->name,
                ];
                \Log::info('Classroom Data: ', ['classroom' => $classroomData]);
                return $classroomData;
            });

        return response()->json($classrooms);
    }
}