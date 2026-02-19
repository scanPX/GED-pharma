<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Entities
        $entity1 = \App\Models\GED\Entity::create([
            'name' => 'Siège Social',
            'description' => 'Administration Centrale',
        ]);
        
        $entity2 = \App\Models\GED\Entity::create([
            'name' => 'Usine de Production',
            'description' => 'Site Industriel',
        ]);

        // 2. Create Departments for Entity 1
        $dept1 = $entity1->departements()->create(['name' => 'Ressources Humaines']);
        $dept2 = $entity1->departements()->create(['name' => 'Finance & Comptabilité']);
        $dept3 = $entity1->departements()->create(['name' => 'IT & Digital']);

        // 3. Create Departments for Entity 2
        $dept4 = $entity2->departements()->create(['name' => 'Production']);
        $dept5 = $entity2->departements()->create(['name' => 'Assurance Qualité']);
        $dept6 = $entity2->departements()->create(['name' => 'Logistique']);

        // 4. Create Functions
        $dept1->fonctions()->createMany([
            ['name' => 'DRH'],
            ['name' => 'Chargé de Recrutement'],
        ]);

        $dept3->fonctions()->createMany([
            ['name' => 'DSI'],
            ['name' => 'Développeur Full Stack'],
            ['name' => 'Administrateur Système'],
        ]);

        $dept5->fonctions()->createMany([
            ['name' => 'Responsable AQ'],
            ['name' => 'Pharmacien Responsable'],
            ['name' => 'Technicien Contrôle Qualité'],
        ]);
    }
}
