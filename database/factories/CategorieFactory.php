<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Categorie;
use App\Models\Municipality;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class CategorieFactory extends Factory
{
    Protected $model = Categorie::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
           // 'name' => $this->faker->sentence,
           'name' => $this->faker->unique()->word(),
            'municipality_id' => Municipality::inRandomOrder()->first()->id,
        ];
    }
}
