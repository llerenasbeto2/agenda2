<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\rol;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class rolFactory extends Factory
{
    Protected $model = rol::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            //'name' => $this->faker->randomElement(['Administrador General', 'Administrador estatal', 'Administrador area', 'Usuario']),
            'name' => $this->faker->randomElement(['Administrador General', 'Usuario']),
        ];
    }
}
