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

class EstatalUsersController extends Controller
{
    public function index(Request $request)
    {
        // Crear la consulta base con relaciones necesarias
        $query = User::with(['rol', 'responsibleClassroom.faculty', 'municipality']);
        
        // Filtrar usuarios según los criterios:
        // - Todos los usuarios con rol_id = 1
        // - Solo usuarios con rol_id = 2 que tengan classroom asociado a una facultad
        $query->where(function ($q) {
            $q->where('rol_id', 1) // Todos los usuarios con rol_id = 1
              ->orWhere(function ($subQuery) {
                  $subQuery->where('rol_id', 2) // Usuarios con rol_id = 2
                           ->whereHas('responsibleClassroom.faculty'); // que tengan classroom con facultad
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
            // Validación - asegurándonos que solo se puedan crear usuarios con rol_id 1 o 2
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'rol_id' => 'required|in:1,2|exists:roles,id',
                'municipality_id' => 'required|exists:municipality,id',
                'responsible_id' => 'nullable|exists:classrooms,id'
            ]);
            
            // Crear el usuario
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'rol_id' => $request->rol_id,
                'municipality_id' => $request->municipality_id,
                'responsible_id' => $request->responsible_id
            ]);
            
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
        
        // Obtener roles específicos (solo los que están permitidos)
        $roles = rol::whereIn('id', [1, 2])->get();
        
        // Obtener todos los municipios
        $municipalities = Municipality::all();
        
        // Obtener todas las aulas (se filtrarán en el frontend según municipio)
        $classrooms = classroom::with('faculty')->get()->map(function ($classroom) {
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
            'municipalities' => $municipalities,
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
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$usuario->id,
            'rol_id' => 'required|in:1,2|exists:roles,id',
            'municipality_id' => 'required|exists:municipality,id',
            'responsible_id' => 'nullable|exists:classrooms,id'
        ]);
        
        $usuario->name = $request->name;
        $usuario->email = $request->email;
        $usuario->rol_id = $request->rol_id;
        $usuario->municipality_id = $request->municipality_id;
        
        // Si el rol es 1 (Usuario), no asignar responsible_id
        $usuario->responsible_id = $request->rol_id == 1 ? null : $request->responsible_id;
        $usuario->save();
        
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
        
        $usuario->delete();
        return redirect()->route('admin.estatal.usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente');
    }
}