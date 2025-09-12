<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\complaint;
use App\Models\User;
use App\Models\sport_field;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class complaintFactory extends Factory
{
    Protected $model = complaint::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
           /* 'user_id' => User::factory(),
            'space_type' => $this->faker->company,
            'complaint_text' => $this->faker->paragraph,*/
            'name_user' => $this->faker->name(),
            'sports_id' => sport_field::inRandomOrder()->first()->id,
            'complaint_text' => $this->faker->paragraphs(3, true),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
