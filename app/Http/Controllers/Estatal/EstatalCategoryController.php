<?php

namespace App\Http\Controllers\Estatal;
use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Municipality;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class EstatalCategoryController extends Controller
{
    public function index()
    {
        $categories = Categorie::all();
        return Inertia::render('Admin/Estatal/Categories/Index', [
            'categories' => $categories
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Estatal/Categories/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:100|unique:categories,name',
        ]);
    
        // Selecciona un municipio al azar
       // $randomMunicipality = Municipality::inRandomOrder()->first();
       $municipality_id = auth()->user()->municipality_id;
    
        Categorie::create([

            'name' => $validated['name'],
            'municipality_id' => $municipality_id,
        ]);
    
        return redirect()->route('admin.estatal.categories.index');
    }

    public function edit(Categorie $category)
    {
        return Inertia::render('Admin/Estatal/Categories/Edit', [
            'category' => $category
        ]);
    }

    public function update(Request $request, Categorie $category)
    {
        $validated = $request->validate([
            'name' => 'required|max:100',
        ]);

        $category->update($validated);

        return redirect()->route('admin.estatal.categories.index');
    }

    public function destroy(Categorie $category)
    {
        $category->delete();
        return redirect()->route('admin.estatal.categories.index');
    }
}
