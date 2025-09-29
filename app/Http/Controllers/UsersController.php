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
use Illuminate\Support\Facades\Log; // Import the Log facade

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
                $rol = Rol::find($request->rol_id);
                if ($rol->name === 'Administrador estatal' && !$value) {
                    $fail('El campo responsable es obligatorio para el rol Administrador estatal.');
                } elseif ($rol->name === 'Administrador estatal') {
                    $faculty = Faculty::where('id', $value)
                        ->where('municipality_id', $request->municipality_id)
                        ->exists();
                    if (!$faculty) {
                        $fail('La facultad seleccionada no es válida para el municipio elegido.');
                    }
                } elseif ($rol->name === 'Administrador area' && !$value) {
                    $fail('El campo responsable es obligatorio para el rol Administrador de área.');
                } elseif ($rol->name === 'Administrador area') {
                    $classroom = Classroom::where('id', $value)
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

    // Obtener el rol actual y anterior para manejar cambios
    $rolActual = Rol::find($request->rol_id);
    $rolAnterior = $usuario->rol;
    
    // Limpiar responsabilidades anteriores si cambió el rol
    if ($rolAnterior && $rolAnterior->id !== $rolActual->id) {
        try {
            if ($rolAnterior->name === 'Administrador estatal') {
                Faculty::where('responsible', $usuario->id)
                    ->update(['responsible' => null]);
            } elseif ($rolAnterior->name === 'Administrador area') {
                Classroom::where('responsible', $usuario->id)
                    ->update(['responsible' => null]);
            }
        } catch (\Exception $e) {
            \Log::error('Error en limpieza por cambio de rol: ' . $e->getMessage());
        }
    }

    // Actualizar el usuario
    $usuario->update([
        'name' => $request->name,
        'email' => $request->email,
        'municipality_id' => $request->municipality_id,
        'rol_id' => $request->rol_id,
        'responsible_id' => $request->responsible_id,
    ]);

    // Actualizar las tablas relacionadas según el rol actual
    if ($rolActual->name === 'Administrador estatal' && $request->responsible_id) {
        try {
            // Limpiar cualquier otro responsable de esta facultad
            Faculty::where('id', $request->responsible_id)
                ->update(['responsible' => null]);
            
            // Asignar el nuevo responsable
            Log::info("id usuario: {$usuario->id}");
            Log::info("id de Facultad: {$request->responsible_id}");
            
            Faculty::where('id', $request->responsible_id)
                ->update(['responsible' => $usuario->id]);
        } catch (\Exception $e) {
            \Log::error('Error en actualización de facultad: ' . $e->getMessage());
        }
        
    } elseif ($rolActual->name === 'Administrador area' && $request->responsible_id) {
        try {
            // Limpiar cualquier otro responsable de esta aula
            Classroom::where('id', $request->responsible_id)
                ->update(['responsible' => null]);
            
            // Asignar el nuevo responsable
            Classroom::where('id', $request->responsible_id)
                ->update(['responsible' => $usuario->id]);
        } catch (\Exception $e) {
            \Log::error('Error en actualización de classroom: ' . $e->getMessage());
        }
    }

    // Si el usuario ya no tiene responsible_id, limpiar sus responsabilidades
    if (!$request->responsible_id) {
        try {
            if ($rolActual->name === 'Administrador estatal') {
                Faculty::where('responsible', $usuario->id)
                    ->update(['responsible' => null]);
            } elseif ($rolActual->name === 'Administrador area') {
                Classroom::where('responsible', $usuario->id)
                    ->update(['responsible' => null]);
            }
        } catch (\Exception $e) {
            \Log::error('Error en limpieza por falta de responsible_id: ' . $e->getMessage());
        }
    }

    // LIMPIEZA FINAL: Manejo de responsabilidades duplicadas
    if ($rolActual->name === 'Administrador estatal') {
        try {
            // Limpiar otras facultades donde el usuario sea responsable (excepto la nueva asignación)
            $query = Faculty::where('responsible', $usuario->id);
            if ($request->responsible_id) {
                $query->where('id', '!=', $request->responsible_id);
            }
            $query->update(['responsible' => null]);
            
        } catch (\Exception $e) {
            \Log::error('Error en limpieza de facultades: ' . $e->getMessage());
        }

        // LIMPIAR TABLA USERS: Otros usuarios del mismo rol con el mismo responsible_id
        if ($request->responsible_id) {
            try {
                User::where('rol_id', $rolActual->id)
                    ->where('responsible_id', $request->responsible_id)
                    ->where('id', '!=', $usuario->id)
                    ->update(['responsible_id' => null]);
                    
            } catch (\Exception $e) {
                \Log::error('Error en limpieza de users para Administrador estatal: ' . $e->getMessage());
            }
        }
        
    } elseif ($rolActual->name === 'Administrador area') {
        try {
            // Limpiar otras aulas donde el usuario sea responsable (excepto la nueva asignación)
            $query = Classroom::where('responsible', $usuario->id);
            if ($request->responsible_id) {
                $query->where('id', '!=', $request->responsible_id);
            }
            $query->update(['responsible' => null]);
            
        } catch (\Exception $e) {
            \Log::error('Error en limpieza de classrooms: ' . $e->getMessage());
        }

        // LIMPIAR TABLA USERS: Otros usuarios del mismo rol con el mismo responsible_id
        if ($request->responsible_id) {
            try {
                User::where('rol_id', $rolActual->id)
                    ->where('responsible_id', $request->responsible_id)
                    ->where('id', '!=', $usuario->id)
                    ->update(['responsible_id' => null]);
                    
            } catch (\Exception $e) {
                \Log::error('Error en limpieza de users para Administrador area: ' . $e->getMessage());
            }
        }
    }

    return redirect()->route('admin.general.usuarios.index')
        ->with('success', 'Usuario actualizado exitosamente');
}
public function destroy(User $usuario)
{
    // LIMPIEZA PREVIA: Limpiar responsabilidades antes de eliminar el usuario
    $rol = $usuario->rol;
    
    if ($rol && $usuario->responsible_id) {
        if ($rol->name === 'Administrador estatal') {
            // Limpiar el campo responsible de la facultad asignada
            try {
                Faculty::where('id', $usuario->responsible_id)
                    ->update(['responsible' => null]);
                \Log::info("Limpiado responsible de facultad ID: {$usuario->responsible_id} para usuario ID: {$usuario->id}");
            } catch (\Exception $e) {
                \Log::error('Error limpiando responsible de facultad: ' . $e->getMessage());
            }
            
        } elseif ($rol->name === 'Administrador area') {
            // Limpiar el campo responsible de la aula asignada
            try {
                Classroom::where('id', $usuario->responsible_id)
                    ->update(['responsible' => null]);
                \Log::info("Limpiado responsible de classroom ID: {$usuario->responsible_id} para usuario ID: {$usuario->id}");
            } catch (\Exception $e) {
                \Log::error('Error limpiando responsible de classroom: ' . $e->getMessage());
            }
        }
    }
    
    // LIMPIEZA ADICIONAL: Limpiar cualquier referencia del usuario como responsible en todas las tablas
    try {
        // Limpiar de facultades donde este usuario sea responsible (por si hay inconsistencias)
        Faculty::where('responsible', $usuario->id)
            ->update(['responsible' => null]);
        
        // Limpiar de classrooms donde este usuario sea responsible (por si hay inconsistencias)
        Classroom::where('responsible', $usuario->id)
            ->update(['responsible' => null]);
        
        \Log::info("Limpiadas todas las referencias del usuario ID: {$usuario->id} como responsible");
    } catch (\Exception $e) {
        \Log::error('Error en limpieza adicional de referencias: ' . $e->getMessage());
    }

    // Finalmente eliminar el usuario
    $usuario->delete();
    
    return redirect()->route('admin.general.usuarios.index')
        ->with('success', 'Usuario eliminado exitosamente');
}
}