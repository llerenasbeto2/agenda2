<?php

namespace App\Http\Controllers\Estatal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\rol;
use App\Models\faculty;
use App\Models\classroom;
use App\Models\Municipality;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Import the Log facade


class EstatalUsersController extends Controller
{
public function index(Request $request)
{
    // Obtener el usuario logueado
    $currentUser = Auth::user();
    $currentUserMunicipalityId = $currentUser->municipality_id;
    
    // Verificar si el usuario es responsable de una facultad (rol_id = 3)
    $responsibleFacultyId = null;
    if ($currentUser->rol_id == 3 && $currentUser->responsible_id) {
        // El usuario es responsable de una facultad
        $responsibleFacultyId = $currentUser->responsible_id;
    }
    
    // Crear la consulta base con relaciones necesarias
    $query = User::with(['rol', 'responsibleClassroom.faculty', 'municipality']);
    
    // Si el usuario es responsable de una facultad, mostrar usuarios del mismo municipio y facultad
    if ($responsibleFacultyId) {
        $query->where(function ($q) use ($currentUserMunicipalityId, $responsibleFacultyId) {
            // Usuarios con rol_id = 1 del mismo municipio
            $q->where(function ($subQ) use ($currentUserMunicipalityId) {
                $subQ->where('rol_id', 1)
                     ->where(function ($municipalityQ) use ($currentUserMunicipalityId) {
                         $municipalityQ->where('municipality_id', $currentUserMunicipalityId)
                                      ->orWhereNull('municipality_id');
                     });
            })
            // O usuarios con rol_id = 2 del mismo municipio que tengan aulas en la facultad
            ->orWhere(function ($subQuery) use ($currentUserMunicipalityId, $responsibleFacultyId) {
                $subQuery->where('rol_id', 2)
                         ->where(function ($municipalityQ) use ($currentUserMunicipalityId) {
                             $municipalityQ->where('municipality_id', $currentUserMunicipalityId)
                                          ->orWhereNull('municipality_id');
                         })
                         ->whereHas('responsibleClassroom', function ($classroomQ) use ($responsibleFacultyId) {
                             $classroomQ->where('faculty_id', $responsibleFacultyId);
                         });
            })
            // O usuarios con rol_id = 2 del mismo municipio que NO tengan aula asignada pero estén en el contexto de la facultad
            ->orWhere(function ($subQuery) use ($currentUserMunicipalityId) {
                $subQuery->where('rol_id', 2)
                         ->where(function ($municipalityQ) use ($currentUserMunicipalityId) {
                             $municipalityQ->where('municipality_id', $currentUserMunicipalityId)
                                          ->orWhereNull('municipality_id');
                         })
                         ->whereDoesntHave('responsibleClassroom');
            });
        });
    } else {
        // Lógica original para usuarios que no son responsables de facultad
        $query->where(function ($q) use ($currentUserMunicipalityId) {
            $q->where(function ($subQ) use ($currentUserMunicipalityId) {
                $subQ->where('rol_id', 1)
                     ->where(function ($municipalityQ) use ($currentUserMunicipalityId) {
                         $municipalityQ->where('municipality_id', $currentUserMunicipalityId)
                                      ->orWhereNull('municipality_id');
                     });
            })
            ->orWhere(function ($subQuery) use ($currentUserMunicipalityId) {
                $subQuery->where('rol_id', 2)
                         ->where(function ($municipalityQ) use ($currentUserMunicipalityId) {
                             $municipalityQ->where('municipality_id', $currentUserMunicipalityId)
                                          ->orWhereNull('municipality_id');
                         });
            });
        });
    }
    
    // Aplicar filtro de búsqueda por nombre si existe
    if ($request->has('name') && !empty($request->name)) {
        $query->where('name', 'like', '%' . $request->name . '%');
    }
    
    // Aplicar filtro por tipo de rol si existe
    if ($request->has('rol_filter') && !empty($request->rol_filter)) {
        $query->where('rol_id', $request->rol_filter);
    }
    
    // Obtener los resultados
    $users = $query->get()->map(function ($user) use ($responsibleFacultyId) {
        // Si el usuario es responsable de un aula, obtener el nombre del aula
        $responsibleName = 'Sin aula';
        if ($user->rol_id == 2) {
            // Buscar en qué aula es responsable
            $classroom = classroom::where('responsible', $user->id)->first();
            if ($classroom) {
                $responsibleName = $classroom->name;
            }
        } elseif ($user->responsibleClassroom) {
            $responsibleName = $user->responsibleClassroom->name;
        }
        
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'rol' => $user->rol ? $user->rol->name : 'Sin rol',
            'rol_id' => $user->rol_id,
            'responsible' => $responsibleName,
            'municipality' => $user->municipality ? $user->municipality->name : 'Sin municipio'
        ];
    });
    
    // Obtener los roles para el filtro
    $roles = rol::whereIn('id', [1, 2])->get();
    
    // Información adicional para debugging (puedes quitarlo en producción)
    $debugInfo = null;
    if ($responsibleFacultyId) {
        $faculty = faculty::find($responsibleFacultyId);
        $debugInfo = [
            'faculty_name' => $faculty ? $faculty->name : 'No encontrada',
            'faculty_id' => $responsibleFacultyId,
            'total_users_found' => $users->count(),
            'municipality_id' => $currentUserMunicipalityId
        ];
    }
    
    // Renderizar la vista con los datos
    return Inertia::render('Admin/Estatal/Users/Index', [
        'users' => $users,
        'roles' => $roles,
        'filters' => $request->only(['name', 'rol_filter']),
        'debugInfo' => $debugInfo // Opcional para debugging
    ]);
}
    
    public function store(Request $request)
    {
        try {
            // Obtener el usuario logueado
            $currentUser = Auth::user();
            $currentUserMunicipalityId = $currentUser->municipality_id;
            
            // Validación
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'rol_id' => 'required|in:1,2|exists:roles,id',
                'responsible_id' => 'nullable|exists:classrooms,id'
            ]);
            
            // Si el usuario actual es responsable de una facultad y está creando un admin de área
            if ($currentUser->rol_id == 3 && $request->rol_id == 2 && $request->responsible_id) {
                // Verificar que el aula pertenezca a la facultad del usuario
                $classroom = classroom::find($request->responsible_id);
                if (!$classroom || $classroom->faculty_id != $currentUser->responsible_id) {
                    return back()->with('error', 'El aula seleccionada no pertenece a tu facultad');
                }
            }
            
            DB::transaction(function () use ($request, $currentUserMunicipalityId) {
                // Crear el usuario
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'rol_id' => $request->rol_id,
                    'municipality_id' => $currentUserMunicipalityId,
                    'responsible_id' => $request->rol_id == 1 ? null : $request->responsible_id
                ]);

                // Sincronizar con tabla classrooms si se asigna responsable
                if ($request->responsible_id && $request->rol_id == 2) {
                    $this->assignNewResponsibilities($request->responsible_id, rol::find($request->rol_id), $user->id);
                }
            });
            
            return redirect()->route('admin.estatal.usuarios.index')
                ->with('success', 'Usuario creado exitosamente');
                
        } catch (\Exception $e) {
            return back()
                ->withInput($request->except('password'))
                ->with('error', 'Error al crear el usuario: ' . $e->getMessage());
        }
    }

    public function edit(User $usuario)
    {
        $currentUser = Auth::user();
        $currentUserMunicipalityId = $currentUser->municipality_id;
        
        // Verificar permisos según el rol del usuario logueado
        if ($currentUser->rol_id == 3) {
            // Si es responsable de facultad, verificar que el usuario a editar sea de su facultad
            if ($usuario->rol_id == 2) {
                // Verificar que el usuario sea responsable de un aula en su facultad
                $classroom = classroom::where('responsible', $usuario->id)
                    ->where('faculty_id', $currentUser->responsible_id)
                    ->first();
                    
                /*if (!$classroom) {
                    return redirect()->route('admin.estatal.usuarios.index')
                        ->with('error', 'No puedes editar este usuario');
                }*/
            } elseif ($usuario->rol_id != 1) {
                return redirect()->route('admin.estatal.usuarios.index')
                    ->with('error', 'No tienes permisos para editar este usuario');
            }
        } else {
            // Lógica original para otros tipos de usuario
            if (!in_array($usuario->rol_id, [1, 2])) {
                return redirect()->route('admin.estatal.usuarios.index')
                    ->with('error', 'No tienes permisos para editar este usuario');
            }
            
            if ($usuario->municipality_id && $usuario->municipality_id !== $currentUserMunicipalityId) {
                return redirect()->route('admin.estatal.usuarios.index')
                    ->with('error', 'No puedes editar usuarios de otros municipios');
            }
        }
        
        // Obtener roles específicos
        $roles = rol::whereIn('id', [1, 2])->get();
        
        // Obtener aulas según el rol del usuario logueado
        if ($currentUser->rol_id == 3 && $currentUser->responsible_id) {
            // Si es responsable de facultad, obtener solo las aulas de su facultad
            $classrooms = classroom::where('faculty_id', $currentUser->responsible_id)
                ->get()
                ->map(function ($classroom) {
                    return [
                        'id' => $classroom->id,
                        'name' => $classroom->name,
                        'faculty_id' => $classroom->faculty_id,
                        'municipality_id' => null
                    ];
                });
        } else {
            // Lógica original
            $classrooms = classroom::with('faculty')
                ->where(function ($query) use ($currentUserMunicipalityId) {
                    $query->whereHas('faculty', function ($subQuery) use ($currentUserMunicipalityId) {
                        $subQuery->where('municipality_id', $currentUserMunicipalityId);
                    })
                    ->orWhereDoesntHave('faculty');
                })
                ->get()
                ->map(function ($classroom) {
                    return [
                        'id' => $classroom->id,
                        'name' => $classroom->name,
                        'faculty_id' => $classroom->faculty_id,
                        'municipality_id' => $classroom->faculty ? $classroom->faculty->municipality_id : null
                    ];
                });
        }

        return Inertia::render('Admin/Estatal/Users/Edit', [
            'user' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'rol_id' => $usuario->rol_id,
                'municipality_id' => $usuario->municipality_id,
                'responsible_id' => $usuario->responsible_id
            ],
            'roles' => $roles,
            'classrooms' => $classrooms
        ]);
    }
    
    public function update(Request $request, User $usuario)
{
    $currentUser = Auth::user();
    $currentUserMunicipalityId = $currentUser->municipality_id;
    
    // Verificar permisos según el rol del usuario logueado
    if ($currentUser->rol_id == 3) {
        if ($usuario->rol_id == 2) {
            $classroom = classroom::where('responsible', $usuario->id)
                ->where('faculty_id', $currentUser->responsible_id)
                ->first();
                
            /*if (!$classroom) {
                return redirect()->route('admin.estatal.usuarios.index')
                    ->with('error', 'No puedes actualizar este usuario');
            }*/
        } elseif ($usuario->rol_id != 1) {
            return redirect()->route('admin.estatal.usuarios.index')
                ->with('error', 'No tienes permisos para actualizar este usuario');
        }
    } else {
        if (!in_array($usuario->rol_id, [1, 2])) {
            return redirect()->route('admin.estatal.usuarios.index')
                ->with('error', 'No tienes permisos para actualizar este usuario');
        }
        
        if ($usuario->municipality_id && $usuario->municipality_id !== $currentUserMunicipalityId) {
            return redirect()->route('admin.estatal.usuarios.index')
                ->with('error', 'No puedes editar usuarios de otros municipios');
        }
    }
    
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users,email,'.$usuario->id,
        'rol_id' => 'required|in:1,2|exists:roles,id',
        'responsible_id' => 'nullable|exists:classrooms,id'
    ]);
    
    // Verificar que el aula pertenezca al ámbito correcto
    if ($request->responsible_id) {
        if ($currentUser->rol_id == 3) {
            $classroom = classroom::find($request->responsible_id);
            if (!$classroom || $classroom->faculty_id != $currentUser->responsible_id) {
                return back()->with('error', 'El aula seleccionada no pertenece a tu facultad');
            }
        } else {
            $classroom = classroom::with('faculty')->find($request->responsible_id);
            if (!$classroom || ($classroom->faculty && $classroom->faculty->municipality_id !== $currentUserMunicipalityId)) {
                return back()->with('error', 'El aula seleccionada no pertenece a tu municipio');
            }
        }
    }
    
    // Obtener el rol actual y anterior para manejar cambios
    $rolActual = Rol::find($request->rol_id);
    $rolAnterior = $usuario->rol;
    $responsibleIdAnterior = $usuario->responsible_id;

    // Actualizar el usuario
    $usuario->update([
        'name' => $request->name,
        'email' => $request->email,
        'rol_id' => $request->rol_id,
        'municipality_id' => $usuario->municipality_id ?: $currentUserMunicipalityId,
        'responsible_id' => $request->rol_id == 1 ? null : $request->responsible_id,
    ]);

    // LIMPIEZA Y ACTUALIZACIÓN DE CLASSROOMS Y USERS
    // Solo para rol "Administrador area" (rol_id = 2)
    if ($rolActual->name === 'Administrador area' && $request->responsible_id) {
        try {
            // 1. Limpiar cualquier otro responsable de esta aula específica
            Classroom::where('id', $request->responsible_id)
                ->update(['responsible' => null]);
            
            // 2. Asignar el nuevo responsable a la aula
            Classroom::where('id', $request->responsible_id)
                ->update(['responsible' => $usuario->id]);
                
            Log::info("Aula {$request->responsible_id} asignada al usuario {$usuario->id}");
            
        } catch (\Exception $e) {
            \Log::error('Error en actualización de classroom: ' . $e->getMessage());
        }
    }

    // LIMPIEZA: Si el usuario cambió de responsible_id o ya no tiene uno
    if ($responsibleIdAnterior && $responsibleIdAnterior != $request->responsible_id) {
        try {
            // Limpiar la responsabilidad anterior en classroom
            Classroom::where('responsible', $usuario->id)
                ->where('id', $responsibleIdAnterior)
                ->update(['responsible' => null]);
                
            Log::info("Limpieza: Removida responsabilidad del aula {$responsibleIdAnterior} del usuario {$usuario->id}");
            
        } catch (\Exception $e) {
            \Log::error('Error en limpieza de classroom anterior: ' . $e->getMessage());
        }
    }

    // LIMPIEZA FINAL: Manejo de responsabilidades duplicadas para Administrador area
    if ($rolActual->name === 'Administrador area') {
        try {
            // 1. Limpiar otras aulas donde el usuario sea responsable (excepto la nueva asignación)
            $query = Classroom::where('responsible', $usuario->id);
            if ($request->responsible_id) {
                $query->where('id', '!=', $request->responsible_id);
            }
            $affectedRows = $query->update(['responsible' => null]);
            
            if ($affectedRows > 0) {
                Log::info("Limpieza: Removidas {$affectedRows} responsabilidades duplicadas de classrooms para usuario {$usuario->id}");
            }
            
        } catch (\Exception $e) {
            \Log::error('Error en limpieza de classrooms duplicados: ' . $e->getMessage());
        }

        // 2. LIMPIAR TABLA USERS: Otros usuarios del mismo rol con el mismo responsible_id
        if ($request->responsible_id) {
            try {
                $affectedUsers = User::where('rol_id', $rolActual->id)
                    ->where('responsible_id', $request->responsible_id)
                    ->where('id', '!=', $usuario->id)
                    ->update(['responsible_id' => null]);
                    
                if ($affectedUsers > 0) {
                    Log::info("Limpieza: Removido responsible_id de {$affectedUsers} usuarios duplicados para classroom {$request->responsible_id}");
                }
                
            } catch (\Exception $e) {
                \Log::error('Error en limpieza de users para Administrador area: ' . $e->getMessage());
            }
        }
    }

    // Si el usuario ya no tiene responsible_id, limpiar sus responsabilidades en classrooms
    if (!$request->responsible_id && $rolActual->name === 'Administrador area') {
        try {
            $affectedRows = Classroom::where('responsible', $usuario->id)
                ->update(['responsible' => null]);
                
            if ($affectedRows > 0) {
                Log::info("Limpieza: Removidas todas las responsabilidades de classrooms para usuario {$usuario->id}");
            }
            
        } catch (\Exception $e) {
            \Log::error('Error en limpieza por falta de responsible_id: ' . $e->getMessage());
        }
    }
    
    return redirect()->route('admin.estatal.usuarios.index')
        ->with('success', 'Usuario actualizado exitosamente');
}
    
 public function destroy(User $usuario)
{
    $currentUser = Auth::user();
    $currentUserMunicipalityId = $currentUser->municipality_id;
    
    // Verificar permisos según el rol del usuario logueado
    if ($currentUser->rol_id == 3) {
        if ($usuario->rol_id == 2) {
            $classroom = classroom::where('responsible', $usuario->id)
                ->where('faculty_id', $currentUser->responsible_id)
                ->first();
                
            if (!$classroom) {
                return redirect()->route('admin.estatal.usuarios.index')
                    ->with('error', 'No puedes eliminar este usuario');
            }
        } elseif ($usuario->rol_id != 1) {
            return redirect()->route('admin.estatal.usuarios.index')
                ->with('error', 'No tienes permisos para eliminar este usuario');
        }
    } else {
        if (!in_array($usuario->rol_id, [1, 2])) {
            return redirect()->route('admin.estatal.usuarios.index')
                ->with('error', 'No tienes permisos para eliminar este usuario');
        }
        
        if ($usuario->municipality_id && $usuario->municipality_id !== $currentUserMunicipalityId) {
            return redirect()->route('admin.estatal.usuarios.index')
                ->with('error', 'No puedes eliminar usuarios de otros municipios');
        }
    }

    DB::transaction(function () use ($usuario) {
        // LIMPIEZA PREVIA: Limpiar responsabilidades antes de eliminar el usuario
        $rol = $usuario->rol;
        
        if ($rol && $usuario->responsible_id) {
            if ($rol->name === 'Administrador area') {
                // Limpiar el campo responsible de la aula asignada específica
                try {
                    Classroom::where('id', $usuario->responsible_id)
                        ->update(['responsible' => null]);
                    \Log::info("Limpiado responsible de classroom ID: {$usuario->responsible_id} para usuario ID: {$usuario->id}");
                } catch (\Exception $e) {
                    \Log::error('Error limpiando responsible de classroom específico: ' . $e->getMessage());
                }
            }
        }
        
        // LIMPIEZA ADICIONAL: Limpiar cualquier referencia del usuario como responsible en classrooms
        // (por si hay inconsistencias o múltiples asignaciones)
        try {
            $affectedRows = Classroom::where('responsible', $usuario->id)
                ->update(['responsible' => null]);
            
            if ($affectedRows > 0) {
                \Log::info("Limpiadas {$affectedRows} referencias del usuario ID: {$usuario->id} como responsible en classrooms");
            }
        } catch (\Exception $e) {
            \Log::error('Error en limpieza adicional de referencias en classrooms: ' . $e->getMessage());
        }

        // Finalmente eliminar el usuario
        $usuario->delete();
        
        \Log::info("Usuario ID: {$usuario->id} eliminado exitosamente con todas sus referencias limpiadas");
    });

    return redirect()->route('admin.estatal.usuarios.index')
        ->with('success', 'Usuario eliminado exitosamente');
}

    /**
     * Métodos auxiliares para manejar sincronización
     */
    private function clearPreviousResponsibilities($responsibleId, $rol)
    {
        if (!$responsibleId || !$rol) return;

        if ($rol->name === 'Administrador area') {
            Classroom::where('responsible', $responsibleId)->update(['responsible' => null]);
        }
    }

    private function assignNewResponsibilities($responsibleId, $rol, $userId)
    {
        if (!$responsibleId || !$rol) return;

        if ($rol->name === 'Administrador area') {
            $classroom = Classroom::find($responsibleId);
            if ($classroom) {
                if ($classroom->responsible && $classroom->responsible != $userId) {
                    User::where('id', $classroom->responsible)
                         ->where('id', '!=', $userId)
                         ->update(['responsible_id' => null]);
                }
                $classroom->update(['responsible' => $userId]);
            }
        }
    }
}