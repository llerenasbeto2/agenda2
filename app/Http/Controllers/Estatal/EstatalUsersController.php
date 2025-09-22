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

class EstatalUsersController extends Controller
{
    public function index(Request $request)
    {
        // Obtener el municipio del admin estatal logueado
        $currentUserMunicipalityId = Auth::user()->municipality_id;
        
        // Crear la consulta base con relaciones necesarias
        $query = User::with(['rol', 'responsibleClassroom.faculty', 'municipality']);
        
        // Filtrar usuarios según los criterios:
        // - Todos los usuarios con rol_id = 1
        // - Solo usuarios con rol_id = 2 que tengan classroom asociado a una facultad
        // - Y que pertenezcan al mismo municipio que el admin estatal logueado
        $query->where(function ($q) use ($currentUserMunicipalityId) {
            $q->where(function ($subQ) use ($currentUserMunicipalityId) {
                $subQ->where('rol_id', 1) // Usuarios con rol_id = 1
                     ->where(function ($municipalityQ) use ($currentUserMunicipalityId) {
                         $municipalityQ->where('municipality_id', $currentUserMunicipalityId)
                                      ->orWhereNull('municipality_id'); // Incluir usuarios sin municipio asignado
                     });
            })
            ->orWhere(function ($subQuery) use ($currentUserMunicipalityId) {
                $subQuery->where('rol_id', 2) // Usuarios con rol_id = 2
                         ->whereHas('responsibleClassroom.faculty') // que tengan classroom con facultad
                         ->where(function ($municipalityQ) use ($currentUserMunicipalityId) {
                             $municipalityQ->where('municipality_id', $currentUserMunicipalityId)
                                          ->orWhereNull('municipality_id'); // Incluir usuarios sin municipio asignado
                         });
            });
        });
        
        // Aplicar filtro de búsqueda por nombre si existe
        if ($request->has('name') && !empty($request->name)) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        // Aplicar filtro por tipo de rol si existe
        if ($request->has('rol_filter') && !empty($request->rol_filter)) {
            $query->where('rol_id', $request->rol_filter);
        }
        
        // Obtener los resultados
        $users = $query->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rol' => $user->rol ? $user->rol->name : 'Sin rol',
                'rol_id' => $user->rol_id,
                'responsible' => $user->responsibleClassroom ? $user->responsibleClassroom->name : 'Sin aula',
                'municipality' => $user->municipality ? $user->municipality->name : 'Sin municipio'
            ];
        });
        
        // Obtener los roles para el filtro
        $roles = rol::whereIn('id', [1, 2])->get();
        
        // Renderizar la vista con los datos
        return Inertia::render('Admin/Estatal/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $request->only(['name', 'rol_filter'])
        ]);
    }
    
    public function store(Request $request)
    {
        try {
            // Obtener el municipio del admin estatal logueado
            $currentUserMunicipalityId = Auth::user()->municipality_id;
            
            // Validación - asegurándonos que solo se puedan crear usuarios con rol_id 1 o 2
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'rol_id' => 'required|in:1,2|exists:roles,id',
                'responsible_id' => 'nullable|exists:classrooms,id'
            ]);
            
            DB::transaction(function () use ($request, $currentUserMunicipalityId) {
                // Crear el usuario con el municipio del admin estatal
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'rol_id' => $request->rol_id,
                    'municipality_id' => $currentUserMunicipalityId, // Asignar automáticamente el municipio del admin
                    'responsible_id' => $request->rol_id == 1 ? null : $request->responsible_id // Si es Usuario (rol_id = 1), no asignar aula
                ]);

                // NUEVA LÓGICA: Sincronizar con tabla classrooms si se asigna responsable
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
        // Verificar que el usuario tenga rol_id 1 o 2 antes de permitir edición
        if (!in_array($usuario->rol_id, [1, 2])) {
            return redirect()->route('admin.estatal.usuarios.index')
                ->with('error', 'No tienes permisos para editar este usuario');
        }
        
        // Obtener el municipio del admin estatal logueado
        $currentUserMunicipalityId = Auth::user()->municipality_id;
        
        // Verificar que el usuario pertenezca al mismo municipio que el admin estatal
        // O permitir edición si el usuario no tiene municipio asignado (para casos legacy)
        if ($usuario->municipality_id && $usuario->municipality_id !== $currentUserMunicipalityId) {
            return redirect()->route('admin.estatal.usuarios.index')
                ->with('error', 'No puedes editar usuarios de otros municipios');
        }
        
        // Obtener roles específicos (solo los que están permitidos)
        $roles = rol::whereIn('id', [1, 2])->get();
        
        // Obtener solo las aulas del municipio del admin estatal logueado
        // También incluir aulas sin municipio asignado para casos legacy
        $classrooms = classroom::with('faculty')
            ->where(function ($query) use ($currentUserMunicipalityId) {
                $query->whereHas('faculty', function ($subQuery) use ($currentUserMunicipalityId) {
                    $subQuery->where('municipality_id', $currentUserMunicipalityId);
                })
                ->orWhereDoesntHave('faculty'); // Incluir aulas sin facultad
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
        // Verificar que el usuario tenga rol_id 1 o 2 antes de permitir actualización
        if (!in_array($usuario->rol_id, [1, 2])) {
            return redirect()->route('admin.estatal.usuarios.index')
                ->with('error', 'No tienes permisos para actualizar este usuario');
        }
        
        // Obtener el municipio del admin estatal logueado
        $currentUserMunicipalityId = Auth::user()->municipality_id;
        
        // Verificar que el usuario pertenezca al mismo municipio que el admin estatal
        // O permitir actualización si el usuario no tiene municipio asignado (para casos legacy)
        if ($usuario->municipality_id && $usuario->municipality_id !== $currentUserMunicipalityId) {
            return redirect()->route('admin.estatal.usuarios.index')
                ->with('error', 'No puedes editar usuarios de otros municipios');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$usuario->id,
            'rol_id' => 'required|in:1,2|exists:roles,id',
            'responsible_id' => 'nullable|exists:classrooms,id'
        ]);
        
        // Si se proporciona responsible_id, verificar que pertenezca al municipio correcto
        if ($request->responsible_id) {
            $classroom = classroom::with('faculty')->find($request->responsible_id);
            if (!$classroom || ($classroom->faculty && $classroom->faculty->municipality_id !== $currentUserMunicipalityId)) {
                return back()->with('error', 'El aula seleccionada no pertenece a tu municipio');
            }
        }
        
        DB::transaction(function () use ($request, $usuario, $currentUserMunicipalityId) {
            // Obtener valores anteriores para comparar cambios
            $oldResponsibleId = $usuario->responsible_id;
            $oldRol = $usuario->rol;

            $usuario->name = $request->name;
            $usuario->email = $request->email;
            $usuario->rol_id = $request->rol_id;
            
            // Si el usuario no tenía municipio asignado, asignar el del admin actual
            if (!$usuario->municipality_id) {
                $usuario->municipality_id = $currentUserMunicipalityId;
            }
            
            // Si el rol es 1 (Usuario), no asignar responsible_id
            $usuario->responsible_id = $request->rol_id == 1 ? null : $request->responsible_id;
            $usuario->save();

            // NUEVA LÓGICA: Sincronizar con las tablas correspondientes
            $newRol = rol::find($request->rol_id);

            // Limpiar responsabilidades anteriores si cambió el responsible_id
            if ($oldResponsibleId != $usuario->responsible_id) {
                $this->clearPreviousResponsibilities($oldResponsibleId, $oldRol);
            }

            // Asignar nuevas responsabilidades
            if ($usuario->responsible_id && $request->rol_id == 2) {
                $this->assignNewResponsibilities($usuario->responsible_id, $newRol, $usuario->id);
            }
        });
        
        return redirect()->route('admin.estatal.usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente');
    }
    
    public function destroy(User $usuario)
    {
        // Verificar que el usuario tenga rol_id 1 o 2 antes de permitir eliminación
        if (!in_array($usuario->rol_id, [1, 2])) {
            return redirect()->route('admin.estatal.usuarios.index')
                ->with('error', 'No tienes permisos para eliminar este usuario');
        }
        
        // Verificar que el usuario pertenezca al mismo municipio que el admin estatal
        // O permitir eliminación si el usuario no tiene municipio asignado (para casos legacy)
        $currentUserMunicipalityId = Auth::user()->municipality_id;
        if ($usuario->municipality_id && $usuario->municipality_id !== $currentUserMunicipalityId) {
            return redirect()->route('admin.estatal.usuarios.index')
                ->with('error', 'No puedes eliminar usuarios de otros municipios');
        }
        
        DB::transaction(function () use ($usuario) {
            // NUEVA LÓGICA: Limpiar responsabilidades antes de eliminar el usuario
            if ($usuario->responsible_id) {
                $this->clearPreviousResponsibilities($usuario->responsible_id, $usuario->rol);
            }

            $usuario->delete();
        });

        return redirect()->route('admin.estatal.usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente');
    }

    /**
     * NUEVOS MÉTODOS AUXILIARES PARA MANEJAR SINCRONIZACIÓN
     */
    private function clearPreviousResponsibilities($responsibleId, $rol)
    {
        if (!$responsibleId || !$rol) return;

        if ($rol->name === 'Administrador area') {
            // Es responsable de un classroom
            Classroom::where('responsible', $responsibleId)->update(['responsible' => null]);
        }
    }

    private function assignNewResponsibilities($responsibleId, $rol, $userId)
    {
        if (!$responsibleId || !$rol) return;

        if ($rol->name === 'Administrador area') {
            // Es responsable de un classroom
            $classroom = Classroom::find($responsibleId);
            if ($classroom) {
                // Limpiar responsable anterior si existe
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