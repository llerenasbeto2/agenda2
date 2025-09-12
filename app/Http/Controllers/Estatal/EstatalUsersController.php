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
        $query = User::with(['rol', 'responsibleClassroom', 'municipality']);
        
        // Aplicar filtro de búsqueda si existe
        if ($request->has('name') && !empty($request->name)) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        // Obtener los resultados
        $users = $query->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rol' => $user->rol ? $user->rol->name : 'Sin rol',
                'responsible' => $user->responsibleClassroom ? $user->responsibleClassroom->name : 'Sin aula',
                'municipality' => $user->municipality ? $user->municipality->name : 'Sin municipio'
            ];
        });
        
        // Renderizar la vista con los datos
        return Inertia::render('Admin/Estatal/Users/Index', [
            'users' => $users,
            'filters' => $request->only('name')
        ]);
    }
    
    public function store(Request $request)
    {
        try {
            // Validación
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'rol_id' => 'required|exists:roles,id',
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
        // Obtener roles específicos (Usuario y Administrador area)
        $roles = rol::whereIn('name', ['Usuario', 'Administrador area'])->get();
        
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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$usuario->id,
            'rol_id' => 'required|exists:roles,id',
            'municipality_id' => 'required|exists:municipality,id',
            'responsible_id' => 'nullable|exists:classrooms,id'
        ]);
        
        $usuario->name = $request->name;
        $usuario->email = $request->email;
        $usuario->rol_id = $request->rol_id;
        $usuario->municipality_id = $request->municipality_id;
        $usuario->responsible_id = $request->rol_id == rol::where('name', 'Usuario')->first()->id ? null : $request->responsible_id;
        $usuario->save();
        
        return redirect()->route('admin.estatal.usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente');
    }
    
    public function destroy(User $usuario)
    {
        $usuario->delete();
        return redirect()->route('admin.estatal.usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente');
    }
}