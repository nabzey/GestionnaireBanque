<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Récupérer tous les comptes existants
        $comptes = \App\Models\Compte::all();

        if ($comptes->isEmpty()) {
            // Si pas de comptes, créer d'abord des clients et comptes
            $clients = \App\Models\Client::factory(3)->create();
            foreach ($clients as $client) {
                $comptes = $comptes->merge($client->comptes()->createMany([
                    [
                        'numero' => 'CPT-' . strtoupper(\Illuminate\Support\Str::random(8)),
                        'solde_initial' => $faker->randomFloat(2, 10000, 500000),
                        'devise' => 'FCFA',
                        'type' => 'cheque',
                    ],
                    [
                        'numero' => 'CPT-' . strtoupper(\Illuminate\Support\Str::random(8)),
                        'solde_initial' => $faker->randomFloat(2, 5000, 100000),
                        'devise' => 'EUR',
                        'type' => 'courant',
                    ],
                ]));
            }
        }

        // Créer des transactions avec des données réalistes
        $transactions = [
            [
                'reference' => 'TXN-DEPOSIT001',
                'type' => 'depot',
                'montant' => 500000,
                'devise' => 'FCFA',
                'description' => 'Dépôt initial de salaire',
                'statut' => 'validee',
                'date_execution' => now()->subDays(30),
            ],
            [
                'reference' => 'TXN-WITHDRAW001',
                'type' => 'retrait',
                'montant' => 100000,
                'devise' => 'FCFA',
                'description' => 'Retrait DAB',
                'statut' => 'validee',
                'date_execution' => now()->subDays(25),
            ],
            [
                'reference' => 'TXN-TRANSFER001',
                'type' => 'virement',
                'montant' => 250000,
                'devise' => 'FCFA',
                'description' => 'Virement vers Orange Money',
                'statut' => 'validee',
                'date_execution' => now()->subDays(20),
            ],
            [
                'reference' => 'TXN-DEPOSIT002',
                'type' => 'depot',
                'montant' => 75000,
                'devise' => 'EUR',
                'description' => 'Dépôt chèque',
                'statut' => 'validee',
                'date_execution' => now()->subDays(15),
            ],
            [
                'reference' => 'TXN-PENDING001',
                'type' => 'retrait',
                'montant' => 50000,
                'devise' => 'FCFA',
                'description' => 'Retrait en attente',
                'statut' => 'en_attente',
                'date_execution' => null,
            ],
        ];

        foreach ($transactions as $transactionData) {
            $compte = $comptes->random();
            $compte->transactions()->create($transactionData);
        }

        // Créer des transactions supplémentaires avec le factory
        foreach ($comptes as $compte) {
            \App\Models\Transaction::factory(rand(2, 5))->create([
                'compte_id' => $compte->id,
            ]);
        }
    }
}
