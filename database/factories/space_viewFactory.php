<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\space_view;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class space_viewFactory extends Factory
{
    Protected $model = space_view::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'name' => $this->faker->company,
            'location' => $this->faker->address,
            'capacity' => $this->faker->numberBetween(10, 50),
            'services' => 'futbol',
            'responsible' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'image' => $this->faker->imageUrl(),
            'created_at' => $this->faker->date,
            'updated_at' => $this->faker->date,
        ];
    }
}
