<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\GED\Role;
use App\Models\GED\Department;
use Illuminate\Support\Facades\Hash;

/**
 * Admin User Seeder
 * 
 * Crée un utilisateur administrateur par défaut pour le système GED
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer le département QA
        $qaDepartment = Department::where('code', 'QA')->first();
        
        // Créer l'utilisateur admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@ged-pharma.local'],
            [
                'name' => 'Administrateur GED',
                'email' => 'admin@ged-pharma.local',
                'password' => Hash::make('Admin@GED2024!'),
                'employee_id' => 'ADM001',
                'title' => 'Administrateur Système',
                'department_id' => $qaDepartment?->id,
                'is_active' => true,
                'can_sign_electronically' => true,
                'signature_pin' => Hash::make('1234'),
                'password_changed_at' => now(),
                'email_verified_at' => now(),
                'training_completed_at' => now(),
            ]
        );

        // Assigner le rôle admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $admin->roles()->syncWithoutDetaching([
                $adminRole->id => [
                    'assigned_by' => $admin->id,
                    'assigned_at' => now(),
                    'assignment_reason' => 'Initial system setup',
                    'is_active' => true,
                ]
            ]);
        }

        $this->command->info('Admin user created:');
        $this->command->info('  Email: admin@ged-pharma.local');
        $this->command->info('  Password: Admin@GED2024!');
        $this->command->info('  PIN: 1234');
        $this->command->warn('  IMPORTANT: Changez ces identifiants en production!');

        // Créer quelques utilisateurs de test
        $this->createTestUsers();
    }

    protected function createTestUsers(): void
    {
        $qaDept = Department::where('code', 'QA')->first();
        $qcDept = Department::where('code', 'QC')->first();
        $raDept = Department::where('code', 'RA')->first();

        $qaManagerRole = Role::where('name', 'qa_manager')->first();
        $qaAnalystRole = Role::where('name', 'qa_analyst')->first();
        $qcAnalystRole = Role::where('name', 'qc_analyst')->first();
        $regulatoryRole = Role::where('name', 'regulatory_affairs')->first();

        $testUsers = [
            [
                'user' => [
                    'name' => 'Sophie Martin',
                    'email' => 'sophie.martin@ged-pharma.local',
                    'password' => Hash::make('Test@GED2024!'),
                    'employee_id' => 'QA001',
                    'title' => 'Responsable Qualité',
                    'department_id' => $qaDept?->id,
                    'is_active' => true,
                    'can_sign_electronically' => true,
                    'signature_pin' => Hash::make('1234'),
                    'password_changed_at' => now(),
                    'email_verified_at' => now(),
                    'training_completed_at' => now(),
                ],
                'role' => $qaManagerRole,
            ],
            [
                'user' => [
                    'name' => 'Pierre Dubois',
                    'email' => 'pierre.dubois@ged-pharma.local',
                    'password' => Hash::make('Test@GED2024!'),
                    'employee_id' => 'QA002',
                    'title' => 'Analyste Qualité',
                    'department_id' => $qaDept?->id,
                    'is_active' => true,
                    'can_sign_electronically' => true,
                    'signature_pin' => Hash::make('1234'),
                    'password_changed_at' => now(),
                    'email_verified_at' => now(),
                    'training_completed_at' => now(),
                ],
                'role' => $qaAnalystRole,
            ],
            [
                'user' => [
                    'name' => 'Marie Leclerc',
                    'email' => 'marie.leclerc@ged-pharma.local',
                    'password' => Hash::make('Test@GED2024!'),
                    'employee_id' => 'QC001',
                    'title' => 'Responsable Contrôle Qualité',
                    'department_id' => $qcDept?->id,
                    'is_active' => true,
                    'can_sign_electronically' => true,
                    'signature_pin' => Hash::make('1234'),
                    'password_changed_at' => now(),
                    'email_verified_at' => now(),
                    'training_completed_at' => now(),
                ],
                'role' => $qcAnalystRole,
            ],
            [
                'user' => [
                    'name' => 'Jean Bernard',
                    'email' => 'jean.bernard@ged-pharma.local',
                    'password' => Hash::make('Test@GED2024!'),
                    'employee_id' => 'RA001',
                    'title' => 'Responsable Affaires Réglementaires',
                    'department_id' => $raDept?->id,
                    'is_active' => true,
                    'can_sign_electronically' => true,
                    'signature_pin' => Hash::make('1234'),
                    'password_changed_at' => now(),
                    'email_verified_at' => now(),
                    'training_completed_at' => now(),
                ],
                'role' => $regulatoryRole,
            ],
        ];

        foreach ($testUsers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['user']['email']],
                $data['user']
            );

            if ($data['role']) {
                $user->roles()->syncWithoutDetaching([
                    $data['role']->id => [
                        'assigned_by' => 1,
                        'assigned_at' => now(),
                        'assignment_reason' => 'Test user setup',
                        'is_active' => true,
                    ]
                ]);
            }
        }

        $this->command->info('Test users created (password: Test@GED2024!)');
    }
}
