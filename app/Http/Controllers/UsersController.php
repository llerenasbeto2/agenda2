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
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    public function index(Request $request)
{
    \Log::info('Search request received:', [
        'name' => $request->name,
        'rol_filter' => $request->rol_filter
    ]);
    
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
    
    // Filtrar por nombre
    if ($request->filled('name')) {
        $searchTerm = trim($request->input('name'));
        \Log::info('Applying filter for name:', ['searchTerm' => $searchTerm]);
        $query->where('name', 'like', '%' . $searchTerm . '%');
    }
    
    // Filtrar por rol_id
    if ($request->filled('rol_filter')) {
        \Log::info('Applying filter for rol_filter:', ['rol_filter' => $request->rol_filter]);
        $query->where('rol_id', $request->rol_filter);
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
        'filters' => $request->only(['name', 'rol_filter']) // IMPORTANTE: incluir ambos filtros
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
        'email' => 'required|string|email|max:255|unique:users,email,' . $usuario->id,
        'municipality_id' => 'required|exists:municipality,id',
        'rol_id' => 'required|exists:roles,id',
        'responsible_id' => [
            'nullable',
            function ($attribute, $value, $fail) use ($request) {
                $rol = Rol::find($request->rol_id);
                
                if (!$rol) {
                    return;
                }

                if ($rol->name === 'Administrador estatal') {
                    if (!$value) {
                        $fail('El campo responsable es obligatorio para el rol Administrador estatal.');
                        return;
                    }
                    
                    $exists = Faculty::where('id', $value)
                        ->where('municipality_id', $request->municipality_id)
                        ->exists();
                        
                    if (!$exists) {
                        $fail('La facultad seleccionada no es válida para el municipio elegido.');
                    }
                } elseif ($rol->name === 'Administrador area') {
                    if (!$value) {
                        $fail('El campo responsable es obligatorio para el rol Administrador de área.');
                        return;
                    }
                    
                    $exists = Classroom::where('id', $value)
                        ->whereHas('faculty', function ($query) use ($request) {
                            $query->where('municipality_id', $request->municipality_id);
                        })
                        ->exists();
                        
                    if (!$exists) {
                        $fail('El aula seleccionada no es válida para el municipio elegido.');
                    }
                }
            }
        ],
    ]);

    $rolActual = Rol::find($request->rol_id);
    $rolAnterior = $usuario->rol;
    $cambioRol = $rolAnterior && $rolAnterior->id !== $rolActual->id;

    DB::beginTransaction();
    
    try {
        // 1. Limpiar responsabilidades del rol anterior si cambió
        if ($cambioRol) {
            $this->limpiarResponsabilidadesPorRol($usuario->id, $rolAnterior->name);
        }

        // 2. Actualizar el usuario
        $usuario->update([
            'name' => $request->name,
            'email' => $request->email,
            'municipality_id' => $request->municipality_id,
            'rol_id' => $request->rol_id,
            'responsible_id' => $request->responsible_id,
        ]);

        // 3. Gestionar responsabilidades según el nuevo rol
        $this->gestionarResponsabilidades(
            $usuario->id,
            $rolActual,
            $request->responsible_id
        );

        DB::commit();
        
        return redirect()->route('admin.general.usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente');
            
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error al actualizar usuario: ' . $e->getMessage());
        
        return redirect()->back()
            ->withInput()
            ->with('error', 'Ocurrió un error al actualizar el usuario');
    }
}

/**
 * Limpia las responsabilidades según el tipo de rol
 */
private function limpiarResponsabilidadesPorRol(int $usuarioId, string $nombreRol): void
{
    if ($nombreRol === 'Administrador estatal') {
        Faculty::where('responsible', $usuarioId)->update(['responsible' => null]);
    } elseif ($nombreRol === 'Administrador area') {
        Classroom::where('responsible', $usuarioId)->update(['responsible' => null]);
    }
}

/**
 * Gestiona las responsabilidades del usuario según su rol
 */
private function gestionarResponsabilidades(int $usuarioId, Rol $rol, ?int $responsibleId): void
{
    if ($rol->name === 'Administrador estatal') {
        // Limpiar TODAS las facultades donde este usuario era responsable
        Faculty::where('responsible', $usuarioId)->update(['responsible' => null]);
        
        if ($responsibleId) {
            // Limpiar otros usuarios con la misma facultad asignada
            User::where('rol_id', $rol->id)
                ->where('responsible_id', $responsibleId)
                ->where('id', '!=', $usuarioId)
                ->update(['responsible_id' => null]);
            
            // Asignar la nueva responsabilidad
            Faculty::where('id', $responsibleId)->update(['responsible' => $usuarioId]);
            
            \Log::info("Usuario {$usuarioId} asignado como responsable de la facultad {$responsibleId}");
        }
        
    } elseif ($rol->name === 'Administrador area') {
        // Limpiar TODAS las aulas donde este usuario era responsable
        Classroom::where('responsible', $usuarioId)->update(['responsible' => null]);
        
        if ($responsibleId) {
            // Limpiar otros usuarios con la misma aula asignada
            User::where('rol_id', $rol->id)
                ->where('responsible_id', $responsibleId)
                ->where('id', '!=', $usuarioId)
                ->update(['responsible_id' => null]);
            
            // Asignar la nueva responsabilidad
            Classroom::where('id', $responsibleId)->update(['responsible' => $usuarioId]);
        }
    }
}
public function destroy(User $usuario)
{
    DB::beginTransaction();
    
    try {
        $rol = $usuario->rol;
        
        // Limpiar todas las referencias del usuario como responsible
        if ($rol) {
            $this->limpiarResponsabilidadesAntesDeBorrar($usuario->id, $rol->name);
        }
        
        // Eliminar el usuario
        $usuario->delete();
        
        DB::commit();
        
        \Log::info("Usuario ID: {$usuario->id} eliminado exitosamente con todas sus responsabilidades");
        
        return redirect()->route('admin.general.usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente');
            
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error("Error al eliminar usuario ID: {$usuario->id}: " . $e->getMessage());
        
        return redirect()->back()
            ->with('error', 'Ocurrió un error al eliminar el usuario');
    }
}

/**
 * Limpia todas las responsabilidades del usuario antes de eliminarlo
 */
private function limpiarResponsabilidadesAntesDeBorrar(int $usuarioId, string $nombreRol): void
{
    if ($nombreRol === 'Administrador estatal') {
        $filasAfectadas = Faculty::where('responsible', $usuarioId)
            ->update(['responsible' => null]);
            
        if ($filasAfectadas > 0) {
            \Log::info("Limpiadas {$filasAfectadas} facultad(es) del usuario ID: {$usuarioId}");
        }
        
    } elseif ($nombreRol === 'Administrador area') {
        $filasAfectadas = Classroom::where('responsible', $usuarioId)
            ->update(['responsible' => null]);
            
        if ($filasAfectadas > 0) {
            \Log::info("Limpiadas {$filasAfectadas} aula(s) del usuario ID: {$usuarioId}");
        }
    }
}
}