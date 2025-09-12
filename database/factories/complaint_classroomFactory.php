<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\complaint_classroom;
use App\Models\classroom;
use App\Models\User;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class complaint_classroomFactory extends Factory
{
    Protected $model = complaint_classroom::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
           /* 'user_id'=> User::factory(),
            'classroom_id'=>classroom::factory(),
            'complaint_text'=>$this->faker->company,
            'created_at'=>$this->faker->date,*/
            'email' => $this->faker->safeEmail(),
            'classroom_id' => classroom::inRandomOrder()->first()->id,
            'complaint_text' => $this->faker->paragraphs(3, true),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
