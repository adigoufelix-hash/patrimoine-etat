<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $comptes = [
            ['name' => 'Administrateur', 'email' => 'admin@patrimoine.tg', 'role' => 'admin', 'service_id' => null],
            ['name' => 'Gestionnaire du patrimoine', 'email' => 'gestionnaire@patrimoine.tg', 'role' => 'gestionnaire', 'service_id' => null],
            ['name' => 'Responsable de service', 'email' => 'responsable@patrimoine.tg', 'role' => 'responsable', 'service_id' => 1],
        ];

        foreach ($comptes as $c) {
            User::firstOrCreate(['email' => $c['email']], $c + ['password' => 'Patrimoine2026!']);
        }
    }
}
