<?php

namespace Database\Factories;
use App\Models\Municipality;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Municipality>
 */
class MunicipalityFactory extends Factory
{
    protected $model = Municipality::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $index = 0;
    $names = ['Colima', 'Vila de Alvarez', 'Coquimatlan', 'Tecoman', 'Manzanillo'];

    return [
        'name' => $names[$index++ % count($names)],
    ];
    }
}
