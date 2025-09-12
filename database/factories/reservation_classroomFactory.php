<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\reservation_classroom;
use App\Models\classroom;
use App\Models\event;
use App\Models\User;
use App\Models\faculty;
use App\Models\Categorie;
use App\Models\Municipality;

use DateTime;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class reservation_classroomFactory extends Factory
{
    Protected $model = reservation_classroom::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
 
     public function definition(): array
     {
         $classroom = Classroom::inRandomOrder()->first();
         $faculty = $classroom ? $classroom->faculty : Faculty::inRandomOrder()->first();
         $startDate = $this->faker->dateTimeBetween('now', '+3 months');
         $endDate = clone $startDate;
         $endDate->modify('+' . $this->faker->numberBetween(1, 4) . ' hours');
         
         $isRecurring = $this->faker->boolean(30); // 30% chance of being recurring
         
         $data = [
             'user_id' => User::factory(),
             'full_name' => $this->faker->name,
             'Email' => substr($this->faker->safeEmail, 0, 50),
             'phone' => $this->faker->phoneNumber,
             'classroom_id' => Classroom::factory(),
             'event_title' => $this->faker->sentence(3),
             'category_type' => Categorie::factory(),
             'attendees' => $this->faker->numberBetween(1, 50),
             'start_datetime' => $startDate->format('Y-m-d H:i:s'), // Convert to string
             'end_datetime' => $endDate->format('Y-m-d H:i:s'),     // Convert to string directly
             'requirements' => $this->faker->paragraph,
             'status' => $this->faker->randomElement(['pendiente', 'Aprobado', 'rechazado', 'cancelado', 'no realizado', 'realizado']),
             'faculty_id' => \App\Models\Faculty::inRandomOrder()->first()->id,
             'municipality_id' => Municipality::inRandomOrder()->first()->id,

         ];
         
         // Add recurring fields if this is a recurring reservation
         if ($isRecurring) {
             $recurringFrequencies = ['daily', 'weekly', 'monthly'];
             $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
             
             $data['repeticion'] = $this->faker->numberBetween(1, 10);
             $data['recurring_frequency'] = $this->faker->randomElement($recurringFrequencies);
             
             // Generate random recurring days based on the frequency
             if ($data['recurring_frequency'] === 'weekly') {
                 // For weekly, select 1-3 random days
                 $selectedDays = $this->faker->randomElements($weekdays, $this->faker->numberBetween(1, 3));
                 $data['recurring_days'] = json_encode($selectedDays);
             } elseif ($data['recurring_frequency'] === 'monthly') {
                 // For monthly, select 1-5 random days of the month
                 $daysOfMonth = $this->faker->randomElements(range(1, 28), $this->faker->numberBetween(1, 5));
                 $data['recurring_days'] = json_encode($daysOfMonth);
             } else {
                 // For daily, might use 'all' or specific days
                 $data['recurring_days'] = json_encode($this->faker->randomElements($weekdays, $this->faker->numberBetween(3, 7)));
             }
             
             // Generate end date for recurring events (1-6 months in the future from start)
             $recurringEndDate = clone $startDate;
             $recurringEndDate->modify('+' . $this->faker->numberBetween(1, 6) . ' months');
             $data['recurring_end_date'] = $recurringEndDate->format('Y-m-d H:i:s'); // Convert to string
             
             // Sometimes add irregular dates
             if ($this->faker->boolean(30)) {
                 $irregularDates = [];
                 $numIrregularDates = $this->faker->numberBetween(1, 3);
                 
                 for ($i = 0; $i < $numIrregularDates; $i++) {
                     $irregularDate = clone $startDate;
                     $irregularDate->modify('+' . $this->faker->numberBetween(1, 30) . ' days');
                     $irregularDates[] = $irregularDate->format('Y-m-d');
                 }
                 
                 $data['irregular_dates'] = json_encode($irregularDates);
             } else {
                 $data['irregular_dates'] = null;
             }
         } else {
             // Non-recurring events have null values for recurring fields
             $data['repeticion'] = null;
             $data['recurring_frequency'] = null;
             $data['recurring_days'] = null;
             $data['recurring_end_date'] = null;
             $data['irregular_dates'] = null;
         }
         
         return $data;
     }
}
