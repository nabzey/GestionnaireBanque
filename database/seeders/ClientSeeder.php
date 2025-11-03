<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Supprimer tous les clients existants avant de créer les nouveaux
        \App\Models\Client::query()->delete();

        $faker = Faker::create();
        // Créer des clients avec des données réalistes depuis la base de données existante
        $clients = [
            [
                'nom' => 'ba',
                'prenom' => 'zeynab',
                'email' => 'zeynabba45@gmail.com',
                'telephone' => '+221773657335',
                'nci' => '2224567890123',
                'date_naissance' => '1995-05-15',
                'adresse' => '123 Rue de la Paix, Dakar',
                'ville' => 'Dakar',
                'pays' => 'Sénégal',
                'statut' => 'actif',
                'code_authentification' => 'ABC123',
            ],
            [
                'nom' => 'Diop',
                'prenom' => 'Amadou',
                'email' => 'amadou.diop@example.com',
                'telephone' => '+221771234567',
                'nci' => '1234567890123',
                'date_naissance' => '1985-03-15',
                'adresse' => '123 Rue de la Paix, Dakar',
                'ville' => 'Dakar',
                'pays' => 'Sénégal',
                'statut' => 'actif',
                'code_authentification' => 'XYZ789',
            ],
            [
                'nom' => 'Niang',
                'prenom' => 'Die',
                'email' => 'khadjuatou@gmail.com',
                'telephone' => '+221776543210',
                'nci' => '9876543210987',
                'date_naissance' => '1992-08-20',
                'adresse' => '456 Avenue de la Liberté, Dakar',
                'ville' => 'Dakar',
                'pays' => 'Sénégal',
                'statut' => 'actif',
                'code_authentification' => 'DEF456',
            ],
            [
                'nom' => 'NDIAYE',
                'prenom' => 'TAPHA',
                'email' => 'taphpandiaye@gmail.com',
                'telephone' => '+221778765432',
                'nci' => '1122334455667',
                'date_naissance' => '1988-12-10',
                'adresse' => '789 Boulevard de la République, Dakar',
                'ville' => 'Dakar',
                'pays' => 'Sénégal',
                'statut' => 'actif',
                'code_authentification' => 'GHI789',
            ],
            [
                'nom' => 'Ba',
                'prenom' => 'bintou',
                'email' => 'dieniange32@gmail.com',
                'telephone' => '+221779876543',
                'nci' => '5566778899001',
                'date_naissance' => '1990-06-25',
                'adresse' => '321 Rue Kermel, Dakar',
                'ville' => 'Dakar',
                'pays' => 'Sénégal',
                'statut' => 'actif',
                'code_authentification' => 'JKL012',
            ],
        ];

        foreach ($clients as $clientData) {
            \App\Models\Client::create($clientData);
        }

        // Créer des clients supplémentaires avec le factory
        \App\Models\Client::factory(10)->create();
    }
}
