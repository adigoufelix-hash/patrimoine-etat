<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
 use App\Models\Categorie;
use App\Models\Bien;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       
$info = Categorie::firstOrCreate(['nom' => 'Informatique']);
$mob  = Categorie::firstOrCreate(['nom' => 'Mobilier']);
Categorie::firstOrCreate(['nom' => 'Véhicules']);
Categorie::firstOrCreate(['nom' => 'Immobilier']);

Bien::firstOrCreate(
    ['code_inventaire' => 'INV-0001'],
    ['designation' => 'Ordinateur portable HP', 'etat' => 'bon', 'categorie_id' => $info->id]
);
Bien::firstOrCreate(
    ['code_inventaire' => 'INV-0002'],
    ['designation' => 'Bureau de direction', 'etat' => 'neuf', 'categorie_id' => $mob->id]
);
}
}
