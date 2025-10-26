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
        $faker = Faker::create();
        // Créer des clients avec des données réalistes
        $clients = [
            [
                'nom' => 'Diop',
                'prenom' => 'Amadou',
                'email' => 'amadou.diop@example.com',
                'telephone' => '+221771234567',
                'date_naissance' => '1985-03-15',
                'adresse' => '123 Rue de la Paix, Dakar',
                'ville' => 'Dakar',
                'pays' => 'Sénégal',
                'statut' => 'actif',
            ],
            [
                'nom' => 'Ndiaye',
                'prenom' => 'Fatou',
                'email' => 'fatou.ndiaye@example.com',
                'telephone' => '+221772345678',
                'date_naissance' => '1990-07-22',
                'adresse' => '456 Avenue Léopold Sédar Senghor, Dakar',
                'ville' => 'Dakar',
                'pays' => 'Sénégal',
                'statut' => 'actif',
            ],
            [
                'nom' => 'Sow',
                'prenom' => 'Mamadou',
                'email' => 'mamadou.sow@example.com',
                'telephone' => '+221773456789',
                'date_naissance' => '1982-11-08',
                'adresse' => '789 Boulevard de la République, Dakar',
                'ville' => 'Dakar',
                'pays' => 'Sénégal',
                'statut' => 'actif',
            ],
            [
                'nom' => 'Ba',
                'prenom' => 'Aissatou',
                'email' => 'aissatou.ba@example.com',
                'telephone' => '+221774567890',
                'date_naissance' => '1995-01-30',
                'adresse' => '321 Rue Kermel, Dakar',
                'ville' => 'Dakar',
                'pays' => 'Sénégal',
                'statut' => 'inactif',
            ],
            [
                'nom' => 'Gueye',
                'prenom' => 'Ibrahima',
                'email' => 'ibrahima.gueye@example.com',
                'telephone' => '+221775678901',
                'date_naissance' => '1978-09-12',
                'adresse' => '654 Avenue Cheikh Anta Diop, Dakar',
                'ville' => 'Dakar',
                'pays' => 'Sénégal',
                'statut' => 'suspendu',
            ],
        ];

        foreach ($clients as $clientData) {
            \App\Models\Client::create($clientData);
        }

        // Créer des clients supplémentaires avec le factory
        \App\Models\Client::factory(10)->create();
    }
}
