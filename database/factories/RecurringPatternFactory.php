<?php

namespace Database\Factories;
use App\Models\reservation_classroom;
use App\Models\RecurringPattern;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecurringPattern>
 */
class RecurringPatternFactory extends Factory
{
    protected $model = RecurringPattern::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $frequencies = ['daily', 'weekly', 'monthly'];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        // Seleccionar días aleatorios para el patrón recurrente
        $selectedDays = $this->faker->randomElements($days, $this->faker->numberBetween(1, 5));
        return [
            //
            'reservation_id' => reservation_classroom::inRandomOrder()->first()->id,
            'repeticion' => $this->faker->numberBetween(1,10),
            'recurring_frequency' => $this->faker->randomElement($frequencies),
            'recurring_days' => json_encode($selectedDays),
            'recurring_end_date' => $this->faker->dateTimeBetween('+1 month', '+6 months'),
            'irregular_dates' => json_encode([
                $this->faker->dateTimeBetween('+1 week', '+2 months')->format('Y-m-d'),
                $this->faker->dateTimeBetween('+1 week', '+2 months')->format('Y-m-d'),
            ]),
        ];
    }
}
