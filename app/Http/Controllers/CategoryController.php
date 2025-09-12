<?php

namespace App\Http\Controllers;
use App\Models\Categorie;
use App\Models\Municipality;
use Illuminate\Http\Request;
use Inertia\Inertia;
class CategoryController extends Controller
{
    public function index()
    {
        // Obtener todas las categorías con información de municipio
        $categories = Categorie::all();
        
        // Obtener todos los municipios para el selector
        $municipalities = Municipality::all();
        
        return Inertia::render('Admin/General/Categories/Index', [
            'categories' => $categories,
            'municipalities' => $municipalities
        ]);
    }

    public function create()
    {
        // Obtener todos los municipios para poder seleccionar uno al crear
        $municipalities = Municipality::all();
        
        return Inertia::render('Admin/General/Categories/Create', [
            'municipalities' => $municipalities
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:100|unique:categories,name',
            'municipality_id' => 'required|exists:municipality,id' // Validar el municipio seleccionado
        ]);
    
        Categorie::create([
            'name' => $validated['name'],
            'municipality_id' => $validated['municipality_id']
        ]);
    
        return redirect()->route('admin.general.categories.index');
    }

    public function edit(Categorie $category)
    {
        // Obtener todos los municipios para poder editar el municipio de la categoría
        $municipalities = Municipality::all();
        
        return Inertia::render('Admin/General/Categories/Edit', [
            'category' => $category,
            'municipalities' => $municipalities
        ]);
    }

    public function update(Request $request, Categorie $category)
    {
        $validated = $request->validate([
            'name' => 'required|max:100',
            'municipality_id' => 'required|exists:municipality,id' // Validar el municipio seleccionado
        ]);

        $category->update($validated);

        return redirect()->route('admin.general.categories.index');
    }

    public function destroy(Categorie $category)
    {
        $category->delete();
        return redirect()->route('admin.general.categories.index');
    }
}
