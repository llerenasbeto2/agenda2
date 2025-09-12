<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\event;
use App\Models\Categorie;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class eventFactory extends Factory
{
    Protected $model = event::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'title' => $this->faker->sentence,
            'category_id' => Categorie::factory(),
            'date' => $this->faker->date,
            'organizer_id' => $this->faker->numberBetween(0, 10),
            'created_at' => $this->faker->date,
            'space_type' => $this->faker->company,
        ];
    }
}
