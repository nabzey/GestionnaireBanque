<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => \App\Models\Transaction::generateReference(),
            'type' => $this->faker->randomElement(['depot', 'retrait', 'virement', 'transfert']),
            'montant' => $this->faker->randomFloat(2, 1000, 100000),
            'devise' => $this->faker->randomElement(['FCFA', 'EUR', 'USD']),
            'description' => $this->faker->sentence(),
            'statut' => $this->faker->randomElement(['en_attente', 'validee', 'rejete', 'annulee']),
            'date_execution' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'compte_id' => \App\Models\Compte::factory(),
        ];
    }
}
