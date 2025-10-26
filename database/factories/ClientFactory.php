<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => $this->faker->lastName(),
            'prenom' => $this->faker->firstName(),
            'email' => $this->faker->unique()->safeEmail(),
            'telephone' => $this->faker->unique()->phoneNumber(),
            'date_naissance' => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'adresse' => $this->faker->address(),
            'ville' => $this->faker->city(),
            'pays' => $this->faker->randomElement(['Sénégal', 'Mali', 'Côte d\'Ivoire', 'Burkina Faso', 'Niger']),
            'statut' => $this->faker->randomElement(['actif', 'inactif', 'suspendu']),
        ];
    }
}
