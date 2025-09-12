<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\classroom_statistic;
use App\Models\faculty;
use App\Models\classroom;
use App\Models\Municipality;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class classroom_statisticFactory extends Factory
{
    Protected $model = classroom_statistic::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $totalEvents = $this->faker->numberBetween(5, 30);
        $totalAttendees = $this->faker->numberBetween(20, 200);
        $averageAttendance = $totalEvents > 0 ? round($totalAttendees / $totalEvents, 2) : 0;
        return [
            //
            /*'classroom_id' => classroom::factory(),
            'faculty_id' => faculty::factory(),
            'total_events' => $this->faker->numberBetween(0, 10),
            'total_attendees' => $this->faker->numberBetween(0, 50),
            'last_event_date' => $this->faker->optional()->date,
            'last_updated' => $this->faker->date,*/
            'municipality_id' => Municipality::inRandomOrder()->first()->id,
            'classroom_id' => classroom::factory(),
            'faculty_id' => faculty::factory(),
            'total_events' => $totalEvents,
            'total_attendees' => $totalAttendees,
            'average_attendance' => $averageAttendance,
            'last_event_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'last_updated' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
