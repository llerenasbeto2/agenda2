<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\classroom;
use App\Models\faculty;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class classroomFactory extends Factory
{
    Protected $model = classroom::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
          /*  'faculty_id' => faculty::factory(), // Relación con Faculty
            'name' => 'Aula ' . $this->faker->randomNumber(3),
            'capacity' => $this->faker->numberBetween(20, 100),
            'services' => 'Wifi, Aire Acondicionado, Proyector',
            'responsible' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,*/
            'faculty_id' => Faculty::inRandomOrder()->first()->id,
            'name' => 'Aula ' . $this->faker->bothify('##??'),
            'capacity' => $this->faker->numberBetween(20, 100),
            'services' => $this->faker->sentences(3, true),
            'responsible' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),

        ];
    }
}
