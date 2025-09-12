<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\rol;
    use App\Models\Municipality;
use App\Models\faculty;
use App\Models\classroom;
use App\Models\sport_field;
use App\Models\Categorie;
use App\Models\event_statistic;
use App\Models\RecurringPattern;
use App\Models\complaint_classroom;
use App\Models\complaint;
//use App\Models\event;
use App\Models\classroom_statistic;
use App\Models\reservation_classroom;
use App\Models\reservation_sport;
//use App\Models\space_view;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolSeeder::class);
        Municipality::factory(5)->create();
      //  rol::factory(1)->create();
        faculty::factory(4)->create()->each(function ($faculty) {
        classroom::factory(3)->create(['faculty_id' => $faculty->id]);
        });
        User::factory(4)->create();
        
        
       
       // sport_field::factory(4)->create();
        Categorie::factory(4)->create();
        //complaint::factory(4)->create();
       // event_statistic::factory(4)->create();
        reservation_classroom::factory(4)->create();
        //RecurringPattern::factory(20)->create();
       // complaint_classroom::factory(4)->create();
        //event::factory(4)->create();
       // classroom_statistic::factory(4)->create();
        
        //reservation_sport::factory(4)->create();
        //space_view::factory(4)->create();

        // User::factory(10)->create();

        /*User::factory()->create([
            'name' => 'Mike Roguez',
            'email' => 'maro@ucol.mx',
            'password' => Hash::make('secret'),
        ]);*/


        $this->call(TenantSeeder::class);
    }
}
