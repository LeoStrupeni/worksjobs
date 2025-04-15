<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */

     protected $model = Job::class;


    public function definition()
    {
        return [
            'client_id' => Client::factory(),
            'visit_datetime' => $this->faker->dateTimeBetween('-3 days', '+10 week'),
            'visit_coords_status' => '0',
            'job_description' => $this->faker->paragraph(),
            'created_at' => now()
        ];
    }
}
