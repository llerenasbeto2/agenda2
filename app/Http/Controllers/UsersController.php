<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\rol;
use App\Models\faculty;
use App\Models\classroom;
use App\Models\Municipality;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Database\QueryException;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        \Log::info('Search request received:', ['name' => $request->name]);
        
        $query = User::with([
            'rol',
            'municipality',
            'responsibleFaculty' => function ($query) {
                $query->select('id', 'name');
            },
            'responsibleClassroom' => function ($query) {
                $query->select('id', 'name');
            }
        ]);
        
        if ($request->filled('name')) {
            $searchTerm = trim($request->input('name'));
            \Log::info('Applying filter for name:', ['searchTerm' => $searchTerm]);
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }
        
        $users = $query->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rol' => $user->rol ? ['id' => $user->rol->id, 'name' => $user->rol->name] : null,
                'municipality' => $user->municipality ? ['id' => $user->municipality->id, 'name' => $user->municipality->name] : null,
                'responsible_id' => $user->responsible_id,
                'responsibleFaculty' => $user->responsibleFaculty ? ['id' => $user->responsibleFaculty->id, 'name' => $user->responsibleFaculty->name] : null,
                'responsibleClassroom' => $user->responsibleClassroom ? ['id' => $user->responsibleClassroom->id, 'name' => $user->responsibleClassroom->name] : null,
            ];
        });
        
        \Log::info('Users returned:', ['count' => $users->count()]);
        
        return Inertia::render('Admin/General/Users/Index', [
            'users' => $users,
            'filters' => $request->only('name')
        ]);
    }

    public function edit(User $usuario)
    {
        return Inertia::render('Admin/General/Users/Edit', [
            'user' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'municipality_id' => $usuario->municipality_id,
                'rol_id' => $usuario->rol_id,
                'responsible_id' => $usuario->responsible_id,
                'rol' => $usuario->rol ? ['id' => $usuario->rol->id, 'name' => $usuario->rol->name] : null,
                'municipality' => $usuario->municipality ? ['id' => $usuario->municipality->id, 'name' => $usuario->municipality->name] : null,
                'responsibleFaculty' => $usuario->responsibleFaculty ? ['id' => $usuario->responsibleFaculty->id, 'name' => $usuario->responsibleFaculty->name] : null,
                'responsibleClassroom' => $usuario->responsibleClassroom ? ['id' => $usuario->responsibleClassroom->id, 'name' => $usuario->responsibleClassroom->name] : null,
            ],
            'roles' => rol::all()->map(function ($rol) {
                return ['id' => $rol->id, 'name' => $rol->name];
            }),
            'faculties' => faculty::with('municipality')->get()->map(function ($faculty) {
                return [
                    'id' => $faculty->id,
                    'name' => $faculty->name,
                    'municipality_id' => $faculty->municipality_id,
                ];
            }),
            'classrooms' => classroom::with(['faculty' => function ($query) {
                $query->select('id', 'municipality_id');
            }])->get()->map(function ($classroom) {
                return [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                    'faculty_id' => $classroom->faculty_id,
                    'municipality_id' => $classroom->faculty ? $classroom->faculty->municipality_id : null,
                ];
            })->filter(function ($classroom) {
                return !is_null($classroom['municipality_id']);
            })->values(),
            'municipalities' => Municipality::all()->map(function ($municipality) {
                return ['id' => $municipality->id, 'name' => $municipality->name];
            })
        ]);
    }

    public function update(Request $request, User $usuario)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$usuario->id,
            'municipality_id' => 'required|exists:municipality,id',
            'rol_id' => 'required|exists:roles,id',
            'responsible_id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    $rol = rol::find($request->rol_id);
                    if ($rol->name === 'Administrador estatal' && !$value) {
                        $fail('El campo responsable es obligatorio para el rol Administrador estatal.');
                    } elseif ($rol->name === 'Administrador estatal') {
                        $faculty = faculty::where('id', $value)
                            ->where('municipality_id', $request->municipality_id)
                            ->exists();
                        if (!$faculty) {
                            $fail('La facultad seleccionada no es válida para el municipio elegido.');
                        }
                    } elseif ($rol->name === 'Administrador area' && !$value) {
                        $fail('El campo responsable es obligatorio para el rol Administrador de área.');
                    } elseif ($rol->name === 'Administrador area') {
                        $classroom = classroom::where('id', $value)
                            ->whereHas('faculty', function ($query) use ($request) {
                                $query->where('municipality_id', $request->municipality_id);
                            })
                            ->exists();
                        if (!$classroom) {
                            $fail('El aula seleccionada no es válida para el municipio elegido.');
                        }
                    }
                }
            ],
        ]);

        $usuario->update([
            'name' => $request->name,
            'email' => $request->email,
            'municipality_id' => $request->municipality_id,
            'rol_id' => $request->rol_id,
            'responsible_id' => $request->responsible_id,
        ]);

        return redirect()->route('admin.general.usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroy(User $usuario)
    {
        $usuario->delete();
        return redirect()->route('admin.general.usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente');
    }
}