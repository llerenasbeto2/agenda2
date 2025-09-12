<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\sport_field;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class sport_fieldFactory extends Factory
{
    Protected $model = sport_field::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'name'=> $this->faker->company,
            'location'=> 'Polideportivo Colima',
            'capacity'=> '25',
            'services'=> 'Acreditacion deportiva',
            'responsible'=> $this->faker->name,
            'email'=> $this->faker->unique()->safeEmail,
            'phone'=> $this->faker->phoneNumber,
        ];
    }
}
