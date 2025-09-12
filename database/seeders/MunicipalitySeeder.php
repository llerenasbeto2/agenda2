<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Municipality;

class MunicipalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //


        $municipalities = [
            'Colima',
            'Vila de Alvarez',
            'Coquimatlan',
            'Tecoman',
            'Manzanillo'
        ];
    
        // Elimina todos los municipios existentes
        Municipality::query()->delete();
    
        // Inserta los nuevos municipios
        foreach ($municipalities as $name) {
            Municipality::create(['name' => $name]);
        }
    }
}
