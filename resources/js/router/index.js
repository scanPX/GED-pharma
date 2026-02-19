import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

// Layouts
import MainLayout from '@/layouts/MainLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';

// Views
import Dashboard from '@/views/Dashboard.vue';
import Login from '@/views/auth/Login.vue';
import DocumentList from '@/views/documents/DocumentList.vue';
import DocumentDetail from '@/views/documents/DocumentDetail.vue';
import DocumentCreate from '@/views/documents/DocumentCreate.vue';
import WorkflowList from '@/views/workflows/WorkflowList.vue';
import WorkflowDetail from '@/views/workflows/WorkflowDetail.vue';
import AuditTrail from '@/views/audit/AuditTrail.vue';
import UserProfile from '@/views/user/UserProfile.vue';
import UserList from '@/views/admin/users/UserList.vue';
import RoleMatrix from '@/views/admin/roles/RoleMatrix.vue';
import SystemSettings from '@/views/admin/settings/SystemSettings.vue';
import WorkflowDefinitionList from '@/views/admin/workflows/DefinitionList.vue';
import WorkflowDefinitionDetail from '@/views/admin/workflows/DefinitionDetail.vue';
import MyTrainings from '@/views/training/MyTrainings.vue';
import TrainingViewer from '@/views/training/TrainingViewer.vue';
import TrainingManagement from '@/views/admin/training/TrainingManagement.vue';
import SignatureList from '@/views/admin/signatures/SignatureList.vue';

const routes = [
    {
        path: '/auth',
        component: AuthLayout,
        children: [
            {
                path: 'login',
                name: 'login',
                component: Login,
                meta: { guest: true }
            },
        ]
    },
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: Dashboard,
                meta: { title: 'Tableau de bord' }
            },
            // Documents
            {
                path: 'documents',
                name: 'documents.index',
                component: DocumentList,
                meta: { title: 'Documents', permission: 'document.read' }
            },
            {
                path: 'documents/create',
                name: 'documents.create',
                component: DocumentCreate,
                meta: { title: 'Nouveau document', permission: 'document.create' }
            },
            {
                path: 'documents/:id',
                name: 'documents.show',
                component: DocumentDetail,
                meta: { title: 'Détail document', permission: 'document.read' },
                props: true
            },
            // Workflows
            {
                path: 'workflows',
                name: 'workflows.index',
                component: WorkflowList,
                meta: { title: 'Workflows', permission: 'workflow.initiate' }
            },
            {
                path: 'workflows/:id',
                name: 'workflows.show',
                component: WorkflowDetail,
                meta: { title: 'Détail workflow', permission: 'workflow.initiate' },
                props: true
            },
            // Audit
            {
                path: 'audit',
                name: 'audit.index',
                component: AuditTrail,
                meta: { title: 'Audit Trail', permission: 'audit.view' }
            },
            // User
            {
                path: 'profile',
                name: 'user.profile',
                component: UserProfile,
                meta: { title: 'Mon profil' }
            },
            // Training (Personal)
            {
                path: 'training',
                name: 'training.my',
                component: MyTrainings,
                meta: { title: 'Mes Formations', permission: 'document.read' }
            },
            {
                path: 'training/:id',
                name: 'training.view',
                component: TrainingViewer,
                meta: { title: 'Lecture Document', permission: 'document.read' },
                props: true
            },
            // Administration
            {
                path: 'admin/users',
                name: 'admin.users.index',
                component: UserList,
                meta: { title: 'Gestion des Utilisateurs', permission: 'user.manage' }
            },
            {
                path: 'admin/roles',
                name: 'admin.roles.index',
                component: RoleMatrix,
                meta: { title: 'Matrice des Rôles', permission: 'user.manage' } // or role.manage if we had it
            },
            {
                path: 'admin/settings',
                name: 'admin.settings.index',
                component: SystemSettings,
                meta: { title: 'Configuration Système', permission: 'system.configure' }
            },
            {
                path: 'admin/workflows',
                name: 'admin.workflows.index',
                component: WorkflowDefinitionList,
                meta: { title: 'Gestion des Workflows', permission: 'workflow.manage' }
            },
            {
                path: 'admin/workflows/:id',
                name: 'admin.workflows.detail',
                component: WorkflowDefinitionDetail,
                meta: { title: 'Configuration Workflow', permission: 'workflow.manage' },
                props: true
            },
            // Admin - Training
            {
                path: 'admin/training',
                name: 'admin.training.index',
                component: TrainingManagement,
                meta: { title: 'Gestion des Formations', permission: 'training.manage' }
            },
            // Admin - Entities
            {
                path: 'admin/entities',
                name: 'admin.entities.index',
                component: () => import('@/views/admin/entities/EntityList.vue'),
                meta: { title: 'Gestion des Entités', permission: 'user.manage' }
            },
            // Admin - Departments
            {
                path: 'admin/departments',
                name: 'admin.departments.index',
                component: () => import('@/views/admin/departments/DepartmentList.vue'),
                meta: { title: 'Gestion des Départements', permission: 'user.manage' }
            },
            // Admin - Functions
            {
                path: 'admin/functions',
                name: 'admin.functions.index',
                component: () => import('@/views/admin/functions/FunctionList.vue'),
                meta: { title: 'Gestion des Fonctions', permission: 'user.manage' }
            },
            // Admin - Signatures
            {
                path: 'admin/signatures',
                name: 'admin.signatures.index',
                component: SignatureList,
                meta: { title: 'Registre des Signatures', permission: 'audit.view' }
            },
        ]
    },
    // Catch-all route
    {
        path: '/:pathMatch(.*)*',
        redirect: '/'
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    console.log('Navigation:', from.path, '->', to.path, 'Auth:', authStore.isAuthenticated);

    // Check if any matched route requires auth
    const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
    const isGuestRoute = to.matched.some(record => record.meta.guest);

    // Vérifier l'authentification
    if (!authStore.user && authStore.token) {
        await authStore.checkAuth();
    }

    // Route protégée
    if (requiresAuth && !authStore.isAuthenticated) {
        console.log('Redirecting to login - not authenticated');
        return next({ name: 'login', query: { redirect: to.fullPath } });
    }

    // Route pour invités (déjà connecté)
    if (isGuestRoute && authStore.isAuthenticated) {
        console.log('Redirecting to dashboard - already logged in');
        return next({ name: 'dashboard' });
    }

    // Vérifier les permissions
    if (to.meta.permission) {
        const hasPerm = authStore.hasPermission(to.meta.permission);
        console.log('Permission check:', to.meta.permission, '=', hasPerm, 'Roles:', authStore.roles);
        if (!hasPerm) {
            console.log('Redirecting to dashboard - no permission');
            return next({ name: 'dashboard' });
        }
    }

    // Mettre à jour le titre
    document.title = to.meta.title
        ? `${to.meta.title} - GED Pharmaceutique`
        : 'GED Pharmaceutique';

    next();
});

export default router;
