<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\rol;
class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        rol::create(['name' => 'Usuario']);
        rol::create(['name' => 'Administrador area']);
        rol::create(['name' => 'Administrador estatal']);
        rol::create(['name' => 'Administrador General']);
    }
}
