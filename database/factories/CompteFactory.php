<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compte>
 */
class CompteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero' => \App\Models\Compte::generateNumero(),
            'solde_initial' => $this->faker->randomFloat(2, 10000, 500000),
            'devise' => $this->faker->randomElement(['FCFA', 'EUR', 'USD']),
            'type' => $this->faker->randomElement(['cheque', 'epargne']),
            'client_id' => \App\Models\Client::factory(),
        ];
    }
}
