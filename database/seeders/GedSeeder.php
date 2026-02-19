<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GED\Role;
use App\Models\GED\Permission;
use App\Models\GED\DocumentStatus;
use App\Models\GED\DocumentCategory;
use App\Models\GED\DocumentType;
use App\Models\GED\Workflow;
use App\Models\GED\WorkflowStep;
use App\Models\GED\Department;

/**
 * GED Database Seeder
 * 
 * Initialise les données de référence pour le module DMS pharmaceutique
 * Conforme GMP, 21 CFR Part 11, ISO 13485
 */
class GedSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDepartments();
        $this->seedPermissions();
        $this->seedRoles();
        $this->seedDocumentStatuses();
        $this->seedDocumentCategories();
        $this->seedDocumentTypes();
        $this->seedWorkflows();
        
        $this->command->info('GED data seeded successfully!');
    }

    protected function seedDepartments(): void
    {
        $departments = [
            ['name' => 'Assurance Qualité', 'code' => 'QA', 'description' => 'Département Assurance Qualité'],
            ['name' => 'Contrôle Qualité', 'code' => 'QC', 'description' => 'Département Contrôle Qualité'],
            ['name' => 'Production', 'code' => 'PROD', 'description' => 'Département Production'],
            ['name' => 'Affaires Réglementaires', 'code' => 'RA', 'description' => 'Département Affaires Réglementaires'],
            ['name' => 'Recherche & Développement', 'code' => 'RD', 'description' => 'Département R&D'],
            ['name' => 'Logistique', 'code' => 'LOG', 'description' => 'Département Logistique et Supply Chain'],
            ['name' => 'Maintenance', 'code' => 'MAINT', 'description' => 'Département Maintenance'],
            ['name' => 'Ressources Humaines', 'code' => 'RH', 'description' => 'Département Ressources Humaines'],
            ['name' => 'Direction', 'code' => 'DIR', 'description' => 'Direction Générale'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['code' => $dept['code']],
                $dept
            );
        }

        $this->command->info('Departments seeded.');
    }

    protected function seedPermissions(): void
    {
        $permissions = [
            // Documents
            ['name' => 'document.create', 'display_name' => 'Créer un document', 'module' => 'documents', 'action' => 'create'],
            ['name' => 'document.read', 'display_name' => 'Consulter les documents', 'module' => 'documents', 'action' => 'read'],
            ['name' => 'document.update', 'display_name' => 'Modifier un document', 'module' => 'documents', 'action' => 'update'],
            ['name' => 'document.delete', 'display_name' => 'Supprimer un document', 'module' => 'documents', 'action' => 'delete'],
            ['name' => 'document.download', 'display_name' => 'Télécharger un document', 'module' => 'documents', 'action' => 'download'],
            ['name' => 'document.print', 'display_name' => 'Imprimer un document', 'module' => 'documents', 'action' => 'print'],
            ['name' => 'document.archive', 'display_name' => 'Archiver un document', 'module' => 'documents', 'action' => 'archive'],
            
            // Workflows
            ['name' => 'workflow.initiate', 'display_name' => 'Initier un workflow', 'module' => 'workflows', 'action' => 'initiate'],
            ['name' => 'workflow.approve', 'display_name' => 'Approuver un workflow', 'module' => 'workflows', 'action' => 'approve', 'requires_signature' => true],
            ['name' => 'workflow.reject', 'display_name' => 'Rejeter un workflow', 'module' => 'workflows', 'action' => 'reject'],
            ['name' => 'workflow.manage', 'display_name' => 'Gérer les workflows', 'module' => 'workflows', 'action' => 'manage'],
            
            // Signatures
            ['name' => 'signature.apply', 'display_name' => 'Appliquer une signature', 'module' => 'signatures', 'action' => 'apply', 'requires_signature' => true],
            ['name' => 'signature.verify', 'display_name' => 'Vérifier une signature', 'module' => 'signatures', 'action' => 'verify'],
            ['name' => 'signature.revoke', 'display_name' => 'Révoquer une signature', 'module' => 'signatures', 'action' => 'revoke'],
            
            // Audit
            ['name' => 'audit.view', 'display_name' => 'Consulter l\'audit trail', 'module' => 'audit', 'action' => 'view'],
            ['name' => 'audit.export', 'display_name' => 'Exporter l\'audit trail', 'module' => 'audit', 'action' => 'export'],
            ['name' => 'audit.verify', 'display_name' => 'Vérifier l\'intégrité audit', 'module' => 'audit', 'action' => 'verify'],
            
            // Users
            ['name' => 'user.manage', 'display_name' => 'Gérer les utilisateurs', 'module' => 'users', 'action' => 'manage'],
            ['name' => 'user.assign_roles', 'display_name' => 'Assigner des rôles', 'module' => 'users', 'action' => 'assign_roles'],
            // System configuration
            ['name' => 'system.configure', 'display_name' => 'Configurer le système', 'module' => 'system', 'action' => 'configure'],
            
            // Training
            ['name' => 'training.assign', 'display_name' => 'Assigner une formation', 'module' => 'training', 'action' => 'assign'],
            ['name' => 'training.acknowledge', 'display_name' => 'Accuser réception formation', 'module' => 'training', 'action' => 'acknowledge'],
            ['name' => 'training.view_all', 'display_name' => 'Voir toutes les formations', 'module' => 'training', 'action' => 'view_all'],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(
                ['name' => $perm['name']],
                $perm
            );
        }

        $this->command->info('Permissions seeded.');
    }

    protected function seedRoles(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrateur',
                'description' => 'Administrateur système avec tous les droits',
                'access_level' => 'admin',
                'can_approve_documents' => true,
                'can_sign_electronically' => true,
                'can_manage_workflows' => true,
                'can_view_audit_trail' => true,
                'can_manage_users' => true,
                'is_system_role' => true,
                'permissions' => Permission::all()->pluck('id'),
            ],
            [
                'name' => 'qa_manager',
                'display_name' => 'Responsable Qualité (QA Manager)',
                'description' => 'Responsable Assurance Qualité - Approbation finale',
                'access_level' => 'approve',
                'can_approve_documents' => true,
                'can_sign_electronically' => true,
                'can_manage_workflows' => true,
                'can_view_audit_trail' => true,
                'can_manage_users' => false,
                'is_system_role' => true,
                'permissions' => ['document.create', 'document.read', 'document.update', 'document.download', 'document.archive', 
                                 'workflow.initiate', 'workflow.approve', 'workflow.reject', 'workflow.manage',
                                 'signature.apply', 'signature.verify', 'audit.view', 'audit.export',
                                 'training.assign', 'training.view_all'],
            ],
            [
                'name' => 'qa_analyst',
                'display_name' => 'Analyste Qualité (QA Analyst)',
                'description' => 'Analyste Assurance Qualité - Revue et approbation',
                'access_level' => 'review',
                'can_approve_documents' => true,
                'can_sign_electronically' => true,
                'can_manage_workflows' => false,
                'can_view_audit_trail' => true,
                'can_manage_users' => false,
                'is_system_role' => false,
                'permissions' => ['document.create', 'document.read', 'document.update', 'document.download',
                                 'workflow.initiate', 'workflow.approve', 'workflow.reject',
                                 'signature.apply', 'signature.verify', 'audit.view',
                                 'training.acknowledge'],
            ],
            [
                'name' => 'qc_analyst',
                'display_name' => 'Analyste Contrôle Qualité (QC)',
                'description' => 'Analyste Contrôle Qualité - Revue technique',
                'access_level' => 'review',
                'can_approve_documents' => true,
                'can_sign_electronically' => true,
                'can_manage_workflows' => false,
                'can_view_audit_trail' => false,
                'can_manage_users' => false,
                'is_system_role' => false,
                'permissions' => ['document.create', 'document.read', 'document.update', 'document.download',
                                 'workflow.initiate', 'workflow.approve',
                                 'signature.apply', 'training.acknowledge'],
            ],
            [
                'name' => 'regulatory_affairs',
                'display_name' => 'Affaires Réglementaires',
                'description' => 'Responsable des Affaires Réglementaires',
                'access_level' => 'approve',
                'can_approve_documents' => true,
                'can_sign_electronically' => true,
                'can_manage_workflows' => false,
                'can_view_audit_trail' => true,
                'can_manage_users' => false,
                'is_system_role' => true,
                'permissions' => ['document.create', 'document.read', 'document.update', 'document.download',
                                 'workflow.initiate', 'workflow.approve', 'workflow.reject',
                                 'signature.apply', 'signature.verify', 'audit.view',
                                 'training.acknowledge'],
            ],
            [
                'name' => 'document_control',
                'display_name' => 'Contrôle Documentaire',
                'description' => 'Responsable du contrôle documentaire',
                'access_level' => 'write',
                'can_approve_documents' => false,
                'can_sign_electronically' => true,
                'can_manage_workflows' => true,
                'can_view_audit_trail' => true,
                'can_manage_users' => false,
                'is_system_role' => false,
                'permissions' => ['document.create', 'document.read', 'document.update', 'document.download', 'document.archive',
                                 'workflow.initiate', 'workflow.manage',
                                 'signature.apply', 'audit.view', 'audit.export',
                                 'training.assign'],
            ],
            [
                'name' => 'standard_user',
                'display_name' => 'Utilisateur Standard',
                'description' => 'Utilisateur avec accès en lecture',
                'access_level' => 'read',
                'can_approve_documents' => false,
                'can_sign_electronically' => false,
                'can_manage_workflows' => false,
                'can_view_audit_trail' => false,
                'can_manage_users' => false,
                'is_system_role' => true,
                'permissions' => ['document.read', 'document.download', 'training.acknowledge'],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );

            // Assigner les permissions
            if (is_array($permissions)) {
                $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
            } else {
                $permissionIds = $permissions;
            }
            $role->permissions()->sync($permissionIds);
        }

        $this->command->info('Roles seeded.');
    }

    protected function seedDocumentStatuses(): void
    {
        $statuses = [
            ['code' => 'DRAFT', 'name' => 'Brouillon', 'color' => '#9CA3AF', 'icon' => 'pencil', 'is_editable' => true, 'is_visible_to_all' => false, 'sort_order' => 1],
            ['code' => 'IN_REVIEW', 'name' => 'En revue', 'color' => '#F59E0B', 'icon' => 'eye', 'is_editable' => false, 'is_visible_to_all' => false, 'sort_order' => 2],
            ['code' => 'PENDING_APPROVAL', 'name' => 'En attente d\'approbation', 'color' => '#3B82F6', 'icon' => 'clock', 'is_editable' => false, 'is_visible_to_all' => false, 'requires_signature' => true, 'sort_order' => 3],
            ['code' => 'APPROVED', 'name' => 'Approuvé', 'color' => '#10B981', 'icon' => 'check-circle', 'is_editable' => false, 'is_visible_to_all' => true, 'requires_signature' => true, 'sort_order' => 4],
            ['code' => 'EFFECTIVE', 'name' => 'En vigueur', 'color' => '#059669', 'icon' => 'badge-check', 'is_editable' => false, 'is_visible_to_all' => true, 'triggers_training' => true, 'sort_order' => 5],
            ['code' => 'SUPERSEDED', 'name' => 'Remplacé', 'color' => '#F97316', 'icon' => 'arrow-right', 'is_editable' => false, 'is_visible_to_all' => true, 'sort_order' => 6],
            ['code' => 'OBSOLETE', 'name' => 'Obsolète', 'color' => '#EF4444', 'icon' => 'x-circle', 'is_editable' => false, 'is_visible_to_all' => true, 'sort_order' => 7],
            ['code' => 'ARCHIVED', 'name' => 'Archivé', 'color' => '#6B7280', 'icon' => 'archive', 'is_editable' => false, 'is_visible_to_all' => false, 'sort_order' => 8],
        ];

        foreach ($statuses as $status) {
            DocumentStatus::updateOrCreate(
                ['code' => $status['code']],
                $status
            );
        }

        $this->command->info('Document statuses seeded.');
    }

    protected function seedDocumentCategories(): void
    {
        $categories = [
            ['code' => 'SOP', 'name' => 'Procédures Opératoires Standards', 'prefix' => 'SOP', 'retention_years' => 15, 'is_gmp_critical' => true, 'requires_training' => true],
            ['code' => 'WI', 'name' => 'Instructions de Travail', 'prefix' => 'WI', 'retention_years' => 10, 'is_gmp_critical' => true, 'requires_training' => true],
            ['code' => 'FORM', 'name' => 'Formulaires', 'prefix' => 'FORM', 'retention_years' => 10, 'is_gmp_critical' => false],
            ['code' => 'SPEC', 'name' => 'Spécifications', 'prefix' => 'SPEC', 'retention_years' => 15, 'is_gmp_critical' => true],
            ['code' => 'VR', 'name' => 'Rapports de Validation', 'prefix' => 'VR', 'retention_years' => 15, 'is_gmp_critical' => true],
            ['code' => 'VP', 'name' => 'Protocoles de Validation', 'prefix' => 'VP', 'retention_years' => 15, 'is_gmp_critical' => true],
            ['code' => 'POL', 'name' => 'Politiques', 'prefix' => 'POL', 'retention_years' => 15, 'is_gmp_critical' => true, 'requires_training' => true],
            ['code' => 'DEV', 'name' => 'Documents de Déviation', 'prefix' => 'DEV', 'retention_years' => 10, 'is_gmp_critical' => true],
            ['code' => 'CAPA', 'name' => 'CAPA', 'prefix' => 'CAPA', 'retention_years' => 10, 'is_gmp_critical' => true],
            ['code' => 'CC', 'name' => 'Change Control', 'prefix' => 'CC', 'retention_years' => 10, 'is_gmp_critical' => true],
            ['code' => 'TRAIN', 'name' => 'Documents de Formation', 'prefix' => 'TRAIN', 'retention_years' => 10, 'is_gmp_critical' => false],
            ['code' => 'QUAL', 'name' => 'Documents Qualité', 'prefix' => 'QUAL', 'retention_years' => 15, 'is_gmp_critical' => true],
        ];

        foreach ($categories as $index => $cat) {
            $cat['sort_order'] = $index + 1;
            DocumentCategory::updateOrCreate(
                ['code' => $cat['code']],
                $cat
            );
        }

        $this->command->info('Document categories seeded.');
    }

    protected function seedDocumentTypes(): void
    {
        $sopCategory = DocumentCategory::where('code', 'SOP')->first();
        $vrCategory = DocumentCategory::where('code', 'VR')->first();
        $vpCategory = DocumentCategory::where('code', 'VP')->first();
        $specCategory = DocumentCategory::where('code', 'SPEC')->first();

        $types = [
            ['code' => 'SOP_QA', 'name' => 'SOP Assurance Qualité', 'category_id' => $sopCategory->id, 'review_period_months' => 24, 'requires_electronic_signature' => true, 'numbering_format' => 'SOP-QA-{YEAR}-{SEQ:4}'],
            ['code' => 'SOP_QC', 'name' => 'SOP Contrôle Qualité', 'category_id' => $sopCategory->id, 'review_period_months' => 24, 'requires_electronic_signature' => true, 'numbering_format' => 'SOP-QC-{YEAR}-{SEQ:4}'],
            ['code' => 'SOP_PROD', 'name' => 'SOP Production', 'category_id' => $sopCategory->id, 'review_period_months' => 24, 'requires_electronic_signature' => true, 'numbering_format' => 'SOP-PROD-{YEAR}-{SEQ:4}'],
            ['code' => 'SOP_ENG', 'name' => 'SOP Engineering', 'category_id' => $sopCategory->id, 'review_period_months' => 24, 'requires_electronic_signature' => true, 'numbering_format' => 'SOP-ENG-{YEAR}-{SEQ:4}'],
            ['code' => 'VR_IQ', 'name' => 'Rapport IQ', 'category_id' => $vrCategory->id, 'review_period_months' => 36, 'requires_electronic_signature' => true, 'numbering_format' => 'VR-IQ-{YEAR}-{SEQ:4}'],
            ['code' => 'VR_OQ', 'name' => 'Rapport OQ', 'category_id' => $vrCategory->id, 'review_period_months' => 36, 'requires_electronic_signature' => true, 'numbering_format' => 'VR-OQ-{YEAR}-{SEQ:4}'],
            ['code' => 'VR_PQ', 'name' => 'Rapport PQ', 'category_id' => $vrCategory->id, 'review_period_months' => 36, 'requires_electronic_signature' => true, 'numbering_format' => 'VR-PQ-{YEAR}-{SEQ:4}'],
            ['code' => 'VP_GEN', 'name' => 'Protocole de Validation', 'category_id' => $vpCategory->id, 'review_period_months' => 36, 'requires_electronic_signature' => true, 'numbering_format' => 'VP-{YEAR}-{SEQ:4}'],
            ['code' => 'SPEC_MP', 'name' => 'Spécification Matière Première', 'category_id' => $specCategory->id, 'review_period_months' => 24, 'requires_electronic_signature' => true, 'numbering_format' => 'SPEC-MP-{YEAR}-{SEQ:4}'],
            ['code' => 'SPEC_PF', 'name' => 'Spécification Produit Fini', 'category_id' => $specCategory->id, 'review_period_months' => 24, 'requires_electronic_signature' => true, 'numbering_format' => 'SPEC-PF-{YEAR}-{SEQ:4}'],
        ];

        foreach ($types as $type) {
            $type['allowed_extensions'] = ['.pdf', '.docx', '.doc'];
            $type['max_file_size_mb'] = 50;
            $type['is_controlled'] = true;
            
            DocumentType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }

        $this->command->info('Document types seeded.');
    }

    protected function seedWorkflows(): void
    {
        // Workflow SOP Standard
        $sopWorkflow = Workflow::updateOrCreate(
            ['code' => 'SOP_APPROVAL'],
            [
                'name' => 'Approbation SOP Standard',
                'description' => 'Workflow d\'approbation standard pour les SOP',
                'type' => 'approval',
                'requires_sequential_approval' => true,
                'requires_all_approvers' => true,
                'min_approvers' => 2,
                'allows_rejection' => true,
                'allows_revision_request' => true,
                'notify_on_submit' => true,
                'notify_on_approve' => true,
                'notify_on_reject' => true,
                'notify_on_complete' => true,
            ]
        );

        $pendingStatus = DocumentStatus::where('code', 'PENDING_APPROVAL')->first();
        $approvedStatus = DocumentStatus::where('code', 'APPROVED')->first();
        $draftStatus = DocumentStatus::where('code', 'DRAFT')->first();
        $qaRole = Role::where('name', 'qa_analyst')->first();
        $qaManagerRole = Role::where('name', 'qa_manager')->first();

        // Étapes du workflow SOP
        WorkflowStep::updateOrCreate(
            ['workflow_id' => $sopWorkflow->id, 'step_order' => 1],
            [
                'name' => 'Revue QA',
                'description' => 'Revue par un analyste Qualité',
                'step_type' => 'review',
                'required_role_id' => $qaRole?->id,
                'requires_comment' => false,
                'requires_signature' => false,
                'timeout_days' => 5,
                'target_status_id' => $pendingStatus?->id,
                'rejection_status_id' => $draftStatus?->id,
            ]
        );

        WorkflowStep::updateOrCreate(
            ['workflow_id' => $sopWorkflow->id, 'step_order' => 2],
            [
                'name' => 'Approbation QA Manager',
                'description' => 'Approbation finale par le Responsable Qualité',
                'step_type' => 'qa_approval',
                'required_role_id' => $qaManagerRole?->id,
                'requires_comment' => true,
                'requires_signature' => true,
                'timeout_days' => 5,
                'target_status_id' => $approvedStatus?->id,
                'rejection_status_id' => $draftStatus?->id,
            ]
        );

        // Workflow Validation
        $validationWorkflow = Workflow::updateOrCreate(
            ['code' => 'VALIDATION_APPROVAL'],
            [
                'name' => 'Approbation Document de Validation',
                'description' => 'Workflow d\'approbation pour les protocoles et rapports de validation',
                'type' => 'validation',
                'requires_sequential_approval' => true,
                'requires_all_approvers' => true,
                'min_approvers' => 3,
                'allows_rejection' => true,
                'allows_revision_request' => true,
            ]
        );

        $regRole = Role::where('name', 'regulatory_affairs')->first();

        WorkflowStep::updateOrCreate(
            ['workflow_id' => $validationWorkflow->id, 'step_order' => 1],
            [
                'name' => 'Revue Technique',
                'description' => 'Revue technique par QC',
                'step_type' => 'review',
                'allowed_roles' => ['qc_analyst', 'qa_analyst'],
                'requires_comment' => true,
                'requires_signature' => false,
                'timeout_days' => 7,
            ]
        );

        WorkflowStep::updateOrCreate(
            ['workflow_id' => $validationWorkflow->id, 'step_order' => 2],
            [
                'name' => 'Approbation QA',
                'description' => 'Approbation par Assurance Qualité',
                'step_type' => 'qa_approval',
                'required_role_id' => $qaRole?->id,
                'requires_comment' => true,
                'requires_signature' => true,
                'timeout_days' => 5,
            ]
        );

        WorkflowStep::updateOrCreate(
            ['workflow_id' => $validationWorkflow->id, 'step_order' => 3],
            [
                'name' => 'Approbation Finale',
                'description' => 'Approbation finale par le QA Manager',
                'step_type' => 'final_approval',
                'required_role_id' => $qaManagerRole?->id,
                'requires_comment' => true,
                'requires_signature' => true,
                'timeout_days' => 5,
                'target_status_id' => $approvedStatus?->id,
                'rejection_status_id' => $draftStatus?->id,
            ]
        );

        $this->command->info('Workflows seeded.');
    }
}
