<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class CompteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        // Créer des comptes avec des données réalistes
        $comptes = [
            [
                'numero' => 'CPT-CHQ001',
                'solde_initial' => 500000,
                'devise' => 'FCFA',
                'type' => 'cheque',
            ],
            [
                'numero' => 'CPT-CHQ002',
                'solde_initial' => 750000,
                'devise' => 'FCFA',
                'type' => 'cheque',
            ],
            [
                'numero' => 'CPT-CRT001',
                'solde_initial' => 100000,
                'devise' => 'EUR',
                'type' => 'courant',
            ],
            [
                'numero' => 'CPT-EPG001',
                'solde_initial' => 200000,
                'devise' => 'FCFA',
                'type' => 'epargne',
            ],
            [
                'numero' => 'CPT-CHQ003',
                'solde_initial' => 300000,
                'devise' => 'USD',
                'type' => 'cheque',
            ],
        ];

        foreach ($comptes as $compteData) {
            \App\Models\Admin::factory()->create([
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
            ])->comptes()->create($compteData);
        }

        // Créer des comptes supplémentaires avec le factory
        \App\Models\Compte::factory(5)->create();
    }
}
