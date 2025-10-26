<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vérifier si les utilisateurs existent déjà
        if (\App\Models\User::count() > 0) {
            return; // Ne pas recréer si déjà existant
        }

        // Créer des utilisateurs de test avec leurs rôles

        // Admin de test - créer directement dans la table users avec type admin
        \App\Models\User::create([
            'name' => 'Admin Principal',
            'email' => 'admin@banque.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'userable_type' => 'admin',
            'userable_id' => 1,
        ]);

        // Les clients existent déjà via ClientSeeder, on crée juste les comptes utilisateurs
        $existingClients = \App\Models\Client::all();

        foreach ($existingClients as $client) {
            \App\Models\User::create([
                'name' => $client->nom . ' ' . $client->prenom,
                'email' => $client->email,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'userable_type' => 'client',
                'userable_id' => $client->id,
            ]);
        }

        // Générer des utilisateurs supplémentaires
        \App\Models\User::factory(5)->create();
    }
}
