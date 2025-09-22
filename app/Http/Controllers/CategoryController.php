<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        // Obtener todas las categorías
        $categories = Categorie::all();
        
        return Inertia::render('Admin/General/Categories/Index', [
            'categories' => $categories
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/General/Categories/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:100|unique:categories,name'
        ]);
    
        Categorie::create([
            'name' => $validated['name']
        ]);
    
        return redirect()->route('admin.general.categories.index');
    }

    public function edit(Categorie $category)
    {
        return Inertia::render('Admin/General/Categories/Edit', [
            'category' => $category
        ]);
    }

    public function update(Request $request, Categorie $category)
    {
        $validated = $request->validate([
            'name' => 'required|max:100'
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