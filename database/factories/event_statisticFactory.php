<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\event_statistic;
use App\Models\faculty;
use App\Models\Municipality;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class event_statisticFactory extends Factory
{
    Protected $model = event_statistic::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $totalEvents = $this->faker->numberBetween(50, 500);
        $totalAttendees = $totalEvents * $this->faker->numberBetween(20, 100);
        $averageAttendance = $totalEvents > 0 ? round($totalAttendees / $totalEvents, 2) : 0;
        return [
            //
           /* 'faculty_id' => faculty::factory(),
            'total_events' => $this->faker->numberBetween(0, 10),
            'total_attendees' => $this->faker->numberBetween(0, 50),
            'most_popular_category' => $this->faker->name,
            'last_updated' => $this->faker->date,*/
            'municipality_id' => Municipality::inRandomOrder()->first()->id,
            'total_events' => $totalEvents,
            'total_attendees' => $totalAttendees,
            'average_attendance' => $averageAttendance,
            'last_updated' => $this->faker->dateTimeBetween('-1 month', 'now')
        ];
    }
}
