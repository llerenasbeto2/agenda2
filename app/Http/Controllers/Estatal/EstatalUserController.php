<?php

namespace App\Http\Controllers\Estatal;



use App\Models\User;
use App\Models\rol;
use App\Models\classroom;
use App\Models\Municipality;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class EstatalUserController extends Controller
{
    //

    public function index(Request $request)
    {
        // Obtener el municipio del administrador estatal autenticado
        $adminMunicipalityId = Auth::user()->municipality_id;

        \Log::info('Search request received for state admin:', ['name' => $request->name, 'municipality_id' => $adminMunicipalityId]);
        
        $query = User::with([
            'rol',
            'municipality',
            'responsibleClassroom' => function ($query) {
                $query->select('id', 'name');
            }
        ])
        ->whereIn('rol_id', [1, 2]) // Solo usuarios con rol "Usuario" o "Administrador area"
        ->where('municipality_id', $adminMunicipalityId); // Solo usuarios del municipio del admin
        
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
                'responsibleClassroom' => $user->responsibleClassroom ? ['id' => $user->responsibleClassroom->id, 'name' => $user->responsibleClassroom->name] : null,
            ];
        });
        
        \Log::info('Users returned:', ['count' => $users->count()]);
        
        return Inertia::render('Admin/State/Users/Index', [
            'users' => $users,
            'filters' => $request->only('name')
        ]);
    }

    public function edit(User $usuario)
    {
        // Verificar que el usuario pertenece al municipio del admin estatal
        if ($usuario->municipality_id !== Auth::user()->municipality_id) {
            abort(403, 'No tienes permiso para editar este usuario.');
        }

        // Verificar que el usuario tiene un rol permitido
        if (!in_array($usuario->rol_id, [1, 2])) {
            abort(403, 'No puedes editar usuarios con este rol.');
        }

        return Inertia::render('Admin/State/Users/Edit', [
            'user' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'municipality_id' => $usuario->municipality_id,
                'rol_id' => $usuario->rol_id,
                'responsible_id' => $usuario->responsible_id,
                'rol' => $usuario->rol ? ['id' => $usuario->rol->id, 'name' => $usuario->rol->name] : null,
                'municipality' => $usuario->municipality ? ['id' => $usuario->municipality->id, 'name' => $usuario->municipality->name] : null,
                'responsibleClassroom' => $usuario->responsibleClassroom ? ['id' => $usuario->responsibleClassroom->id, 'name' => $usuario->responsibleClassroom->name] : null,
            ],
            'roles' => rol::whereIn('id', [1, 2])->get()->map(function ($rol) {
                return ['id' => $rol->id, 'name' => $rol->name];
            }),
            'classrooms' => classroom::with(['faculty' => function ($query) {
                $query->select('id', 'municipality_id');
            }])
            ->whereHas('faculty', function ($query) {
                $query->where('municipality_id', Auth::user()->municipality_id);
            })
            ->get()
            ->map(function ($classroom) {
                return [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                    'faculty_id' => $classroom->faculty_id,
                    'municipality_id' => $classroom->faculty ? $classroom->faculty->municipality_id : null,
                ];
            })->filter(function ($classroom) {
                return !is_null($classroom['municipality_id']);
            })->values(),
            'municipalities' => Municipality::where('id', Auth::user()->municipality_id)->get()->map(function ($municipality) {
                return ['id' => $municipality->id, 'name' => $municipality->name];
            })
        ]);
    }

    public function update(Request $request, User $usuario)
    {
        // Verificar que el usuario pertenece al municipio del admin estatal
        if ($usuario->municipality_id !== Auth::user()->municipality_id) {
            abort(403, 'No tienes permiso para actualizar este usuario.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$usuario->id,
            'municipality_id' => 'required|exists:municipality,id|in:'.Auth::user()->municipality_id,
            'rol_id' => 'required|in:1,2', // Solo permite "Usuario" y "Administrador area"
            'responsible_id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    $rol = rol::find($request->rol_id);
                    if ($rol->name === 'Administrador area' && !$value) {
                        $fail('El campo responsable es obligatorio para el rol Administrador de área.');
                    } elseif ($rol->name === 'Administrador area') {
                        $classroom = classroom::where('id', $value)
                            ->whereHas('faculty', function ($query) {
                                $query->where('municipality_id', Auth::user()->municipality_id);
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

        return redirect()->route('admin.state.usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroy(User $usuario)
    {
        // Verificar que el usuario pertenece al municipio del admin estatal
        if ($usuario->municipality_id !== Auth::user()->municipality_id) {
            abort(403, 'No tienes permiso para eliminar este usuario.');
        }

        // Verificar que el usuario tiene un rol permitido
        if (!in_array($usuario->rol_id, [1, 2])) {
            abort(403, 'No puedes eliminar usuarios con este rol.');
        }

        $usuario->delete();
        return redirect()->route('admin.state.usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente');
    }
}
