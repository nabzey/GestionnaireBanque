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
        // Supprimer tous les comptes existants avant de créer les nouveaux
        \App\Models\Compte::query()->delete();

        $faker = Faker::create();
        // Créer des comptes avec des données réalistes depuis la base de données existante
        $comptes = [
            [
                'numero' => 'CPT-TEST001',
                'solde_initial' => 25000,
                'devise' => 'FCFA',
                'type' => 'cheque',
                'statut' => 'bloque',
                'motif_blocage' => 'Test blocage',
            ],
            [
                'numero' => 'CPT-TEST002',
                'solde_initial' => 50000,
                'devise' => 'FCFA',
                'type' => 'epargne',
                'statut' => 'actif',
            ],
            [
                'numero' => 'CPT-EPG001',
                'solde_initial' => 100000,
                'devise' => 'FCFA',
                'type' => 'epargne',
                'statut' => 'actif',
            ],
            [
                'numero' => 'CPT-LBPV0KNM',
                'solde_initial' => 75000,
                'devise' => 'FCFA',
                'type' => 'cheque',
                'statut' => 'actif',
            ],
            [
                'numero' => 'CPT-FNYRHRBX',
                'solde_initial' => 60000,
                'devise' => 'FCFA',
                'type' => 'cheque',
                'statut' => 'actif',
            ],
        ];

        // Associer les comptes aux clients existants
        $clients = \App\Models\Client::all();

        foreach ($comptes as $index => $compteData) {
            if (isset($clients[$index])) {
                $clients[$index]->comptes()->create($compteData);
            }
        }

        // Créer des comptes supplémentaires avec le factory
        \App\Models\Compte::factory(5)->create();
    }
}
