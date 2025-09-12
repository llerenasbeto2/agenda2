<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\faculty;
use App\Models\Municipality;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class facultyFactory extends Factory
{
    Protected $model = faculty::class;

    public function definition(): array
    {
        
        $municipality = Municipality::count() > 0 
            ? Municipality::inRandomOrder()->first() 
            : Municipality::factory()->create();

        return [
            //
            'name' => 'Facultad de ' . $this->faker->words(2, true),
            'location' => $this->faker->address(),
            'responsible' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'municipality_id' => Municipality::inRandomOrder()->first()->id,
            'capacity' => $this->faker->numberBetween(1, 100),
            'image' => $this->faker->imageUrl(640, 480, 'business', true),
            'services' => $this->faker->paragraph(3),
            'description' => $this->faker->paragraph(5),
        ];
    }
}