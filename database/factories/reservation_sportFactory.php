<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\reservation_sport;
use App\Models\sport_field;
use App\Models\event;
use App\Models\User;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class reservation_sportFactory extends Factory
{
    Protected $model = reservation_sport::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sportField = sport_field::inRandomOrder()->first();
        $startDate = $this->faker->dateTimeBetween('now', '+3 months');
        $endDate = clone $startDate;
        $endDate->modify('+' . $this->faker->numberBetween(1, 3) . ' hours');
        
        $statuses = ['pending', 'approved', 'rejected', 'cancelled'];
        $eventTypes = [1, 2, 3, 4]; // Códigos para diferentes tipos de eventos
        return [
            //
            'user_id' => User::factory(),
            'full_name' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
            'sports_id' => sport_field::factory(),
            'event_title' => $this->faker->name,
            //'event_type' => event::factory(),
            'event_type' => $this->faker->randomElement($eventTypes),
            'attendees' => $this->faker->numberBetween(1, 50),
            'start_datetime' => $this->faker->date,
            'end_datetime' => $this->faker->date,
            //'requirements' => $this->faker->optional()->sentence,
            'requirements' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement($statuses),
        ];
    }
}
