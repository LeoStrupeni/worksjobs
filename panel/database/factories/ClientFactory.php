<?php

namespace Database\Factories;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = Client::class;

    public function definition()
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'type_doc' => $this->faker->randomElement(['1', '2', '3']),
            'num_doc' => $this->faker->numberBetween(20000000,30123456789)
        ];
    }
}
