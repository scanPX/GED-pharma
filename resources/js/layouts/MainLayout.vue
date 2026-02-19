<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <aside 
      :class="[
        'fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 flex flex-col transform transition-transform duration-300 lg:relative lg:translate-x-0',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
      ]"
    >
      <!-- Logo -->
      <div class="flex items-center justify-between h-16 px-6 bg-slate-800">
        <div class="flex items-center space-x-3">
          <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
            <DocumentTextIcon class="w-5 h-5 text-white" />
          </div>
          <span class="text-white font-bold text-lg">GED Pharma</span>
        </div>
        <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <!-- Navigation -->
    <nav class="flex-1 mt-6 px-3 overflow-y-auto pb-20">
        <div class="space-y-1">
          <router-link
            v-for="item in navigation"
            :key="item.name"
            :to="item.to"
            :class="[
              isActiveRoute(item.to) 
                ? 'bg-slate-800 text-white' 
                : 'text-gray-300 hover:bg-slate-800 hover:text-white',
              'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors'
            ]"
          >
            <component 
              :is="item.icon" 
              :class="[
                isActiveRoute(item.to) ? 'text-blue-400' : 'text-gray-400 group-hover:text-white',
                'mr-3 h-5 w-5 flex-shrink-0'
              ]" 
            />
            {{ item.name }}
            <span 
              v-if="item.badge" 
              class="ml-auto bg-blue-600 text-white text-xs px-2 py-0.5 rounded-full"
            >
              {{ item.badge }}
            </span>
          </router-link>
        </div>

        <!-- Section Qualité -->
        <div class="mt-8">
          <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
            Qualité & Conformité
          </h3>
          <div class="mt-3 space-y-1">
            <router-link
              v-for="item in qualityNavigation"
              :key="item.name"
              :to="item.to"
              :class="[
                isActiveRoute(item.to) 
                  ? 'bg-slate-800 text-white' 
                  : 'text-gray-300 hover:bg-slate-800 hover:text-white',
                'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors'
              ]"
            >
              <component 
                :is="item.icon" 
                :class="[
                  isActiveRoute(item.to) ? 'text-emerald-400' : 'text-gray-400 group-hover:text-white',
                  'mr-3 h-5 w-5 flex-shrink-0'
                ]" 
              />
              {{ item.name }}
            </router-link>
          </div>
        </div>

        <!-- Administration -->
        <div v-if="authStore.hasPermission('user.manage')" class="mt-8">
          <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
            Administration
          </h3>
          <div class="mt-3 space-y-1">
             <router-link
              to="/admin/roles"
              :class="[
                isActiveRoute('/admin/roles') 
                  ? 'bg-slate-800 text-white' 
                  : 'text-gray-300 hover:bg-slate-800 hover:text-white',
                'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors'
              ]"
            >
              <ShieldCheckIcon 
                :class="[
                  isActiveRoute('/admin/roles') ? 'text-green-400' : 'text-gray-400 group-hover:text-white',
                  'mr-3 h-5 w-5 flex-shrink-0'
                ]" 
              />
              Rôles & Permissions
            </router-link>
            <router-link
              to="/admin/settings"
              :class="[
                isActiveRoute('/admin/settings') 
                  ? 'bg-slate-800 text-white' 
                  : 'text-gray-300 hover:bg-slate-800 hover:text-white',
                'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors'
              ]"
            >
              <Cog6ToothIcon 
                :class="[
                  isActiveRoute('/admin/settings') ? 'text-orange-400' : 'text-gray-400 group-hover:text-white',
                  'mr-3 h-5 w-5 flex-shrink-0'
                ]" 
              />
              Configuration
            </router-link>
            <router-link
              to="/admin/users"
              :class="[
                isActiveRoute('/admin/users') 
                  ? 'bg-slate-800 text-white' 
                  : 'text-gray-300 hover:bg-slate-800 hover:text-white',
                'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors'
              ]"
            >
              <UsersIcon 
                :class="[
                  isActiveRoute('/admin/users') ? 'text-purple-400' : 'text-gray-400 group-hover:text-white',
                  'mr-3 h-5 w-5 flex-shrink-0'
                ]" 
              />
              Utilisateurs
            </router-link>

            <router-link
              to="/admin/workflows"
              :class="[
                isActiveRoute('/admin/workflows') 
                  ? 'bg-slate-800 text-white' 
                  : 'text-gray-300 hover:bg-slate-800 hover:text-white',
                'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors'
              ]"
            >
              <ClipboardDocumentCheckIcon 
                :class="[
                  isActiveRoute('/admin/workflows') ? 'text-cyan-400' : 'text-gray-400 group-hover:text-white',
                  'mr-3 h-5 w-5 flex-shrink-0'
                ]" 
              />
              Gestion des Workflows
            </router-link>

            <router-link
              to="/admin/entities"
              :class="[
                isActiveRoute('/admin/entities') 
                  ? 'bg-slate-800 text-white' 
                  : 'text-gray-300 hover:bg-slate-800 hover:text-white',
                'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors'
              ]"
            >
              <BuildingOfficeIcon 
                :class="[
                  isActiveRoute('/admin/entities') ? 'text-indigo-400' : 'text-gray-400 group-hover:text-white',
                  'mr-3 h-5 w-5 flex-shrink-0'
                ]" 
              />
              Gestion des Entités
            </router-link>

            <router-link
              to="/admin/departments"
              :class="[
                isActiveRoute('/admin/departments') 
                  ? 'bg-slate-800 text-white' 
                  : 'text-gray-300 hover:bg-slate-800 hover:text-white',
                'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors'
              ]"
            >
              <RectangleGroupIcon 
                :class="[
                  isActiveRoute('/admin/departments') ? 'text-teal-400' : 'text-gray-400 group-hover:text-white',
                  'mr-3 h-5 w-5 flex-shrink-0'
                ]" 
              />
              Départements
            </router-link>

            <router-link
              to="/admin/functions"
              :class="[
                isActiveRoute('/admin/functions') 
                  ? 'bg-slate-800 text-white' 
                  : 'text-gray-300 hover:bg-slate-800 hover:text-white',
                'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors'
              ]"
            >
              <BriefcaseIcon 
                :class="[
                  isActiveRoute('/admin/functions') ? 'text-rose-400' : 'text-gray-400 group-hover:text-white',
                  'mr-3 h-5 w-5 flex-shrink-0'
                ]" 
              />
              Fonctions
            </router-link>

            <router-link
              to="/admin/training"
              :class="[
                isActiveRoute('/admin/training') 
                  ? 'bg-slate-800 text-white' 
                  : 'text-gray-300 hover:bg-slate-800 hover:text-white',
                'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors'
              ]"
            >
              <AcademicCapIcon 
                :class="[
                  isActiveRoute('/admin/training') ? 'text-blue-400' : 'text-gray-400 group-hover:text-white',
                  'mr-3 h-5 w-5 flex-shrink-0'
                ]" 
              />
              Gestion Formations
            </router-link>
          </div>
        </div>
      </nav>

      <!-- User info (bottom) -->
      <div class="absolute bottom-0 left-0 right-0 p-4 bg-slate-800">
        <div class="flex items-center">
          <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-medium">
            {{ authStore.userInitials }}
          </div>
          <div class="ml-3 flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate">{{ authStore.userName }}</p>
            <p class="text-xs text-gray-400 truncate">{{ primaryRole }}</p>
          </div>
          <button @click="logout" class="p-2 text-gray-400 hover:text-white rounded-lg hover:bg-slate-700">
            <ArrowRightOnRectangleIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top bar -->
      <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="flex items-center justify-between h-16 px-4 sm:px-6">
          <div class="flex items-center space-x-4">
            <button 
              @click="sidebarOpen = true" 
              class="lg:hidden p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100"
            >
              <Bars3Icon class="w-6 h-6" />
            </button>
            
            <!-- Breadcrumb -->
            <nav class="hidden sm:flex items-center space-x-2 text-sm">
              <router-link to="/" class="text-gray-500 hover:text-gray-700">
                <HomeIcon class="w-4 h-4" />
              </router-link>
              <ChevronRightIcon class="w-4 h-4 text-gray-400" />
              <span class="text-gray-900 font-medium">{{ currentPageTitle }}</span>
            </nav>
          </div>

          <div class="flex items-center space-x-4">
            <!-- Recherche rapide -->
            <div class="hidden md:block relative">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Rechercher un document..."
                class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                @keyup.enter="performSearch"
              />
              <MagnifyingGlassIcon class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" />
            </div>

            <!-- Notifications -->
            <button class="relative p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100">
              <BellIcon class="w-6 h-6" />
              <span 
                v-if="notificationCount > 0"
                class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
              >
                {{ notificationCount > 9 ? '9+' : notificationCount }}
              </span>
            </button>

            <!-- Actions rapides -->
            <button
              v-if="authStore.hasPermission('document.create')"
              @click="$router.push({ name: 'documents.create' })"
              class="hidden sm:flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
            >
              <PlusIcon class="w-4 h-4 mr-2" />
              Nouveau document
            </button>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-auto p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto h-full">
          <router-view v-slot="{ Component }">
            <transition name="fade" mode="out-in">
              <component :is="Component" />
            </transition>
          </router-view>
        </div>
      </main>
    </div>

    <!-- Overlay mobile -->
    <div 
      v-if="sidebarOpen" 
      class="fixed inset-0 bg-black/50 z-40 lg:hidden"
      @click="sidebarOpen = false"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useWorkflowStore } from '@/stores/workflows';
import {
  HomeIcon,
  DocumentTextIcon,
  FolderIcon,
  ClipboardDocumentCheckIcon,
  UsersIcon,
  ShieldCheckIcon,
  ChartBarIcon,
  ArrowRightOnRectangleIcon,
  Bars3Icon,
  XMarkIcon,
  BellIcon,
  MagnifyingGlassIcon,
  PlusIcon,
  ChevronRightIcon,
  Cog6ToothIcon,
  AcademicCapIcon,
  IdentificationIcon,
  BuildingOfficeIcon,
  RectangleGroupIcon,
  BriefcaseIcon
} from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const workflowStore = useWorkflowStore();

const sidebarOpen = ref(false);
const searchQuery = ref('');

const navigation = computed(() => [
  { name: 'Tableau de bord', to: '/', icon: HomeIcon },
  { name: 'Documents', to: '/documents', icon: FolderIcon },
  { 
    name: 'Workflows', 
    to: '/workflows', 
    icon: ClipboardDocumentCheckIcon,
    badge: workflowStore.pendingCount || null
  },
]);

const qualityNavigation = computed(() => [
  { name: 'Audit Trail', to: '/audit', icon: ShieldCheckIcon },
  { name: 'Registre des Signatures', to: '/admin/signatures', icon: IdentificationIcon },
  { name: 'Mes Formations', to: '/training', icon: AcademicCapIcon },
]);

const currentPageTitle = computed(() => route.meta.title || 'Dashboard');

const primaryRole = computed(() => {
  const roleNames = {
    admin: 'Administrateur',
    qa_manager: 'QA Manager',
    qa_analyst: 'QA Analyst',
    qc_analyst: 'QC Analyst',
    regulatory_affairs: 'Affaires Réglementaires',
    document_control: 'Contrôle Documentaire',
    standard_user: 'Utilisateur',
  };
  
  if (authStore.roles.length > 0) {
    return roleNames[authStore.roles[0]] || authStore.roles[0];
  }
  return 'Utilisateur';
});

const notificationCount = ref(3); // TODO: Connecter aux vraies notifications

function isActiveRoute(to) {
  if (to === '/') {
    return route.path === '/';
  }
  return route.path.startsWith(to);
}

function performSearch() {
  if (searchQuery.value.trim()) {
    router.push({ name: 'documents.index', query: { search: searchQuery.value } });
    searchQuery.value = '';
  }
}

async function logout() {
  await authStore.logout();
  router.push({ name: 'login' });
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
