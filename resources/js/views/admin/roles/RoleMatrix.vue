<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">Matrice des Rôles et Permissions</h1>
        <p class="mt-2 text-sm text-gray-700">Gérez les permissions pour chaque rôle du système.</p>
      </div>
    </div>

    <div v-if="loading" class="mt-8 text-center text-gray-500">
      Chargement...
    </div>

    <div v-else class="mt-8 flex flex-col">
      <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
          <div class="shadow ring-1 ring-black ring-opacity-5 md:rounded-lg overflow-hidden bg-white">
            
            <div class="p-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
              <span class="text-sm font-medium text-gray-500">Module</span>
              <div class="flex space-x-2">
                 <button @click="saveChanges" :disabled="!hasChanges || saving" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                   {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                 </button>
              </div>
            </div>

            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6 w-1/3">Permission</th>
                  <th v-for="role in roles" :key="role.id" scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">
                    {{ role.display_name }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <template v-for="(modulePermissions, moduleName) in permissions" :key="moduleName">
                  <tr class="bg-gray-100">
                    <td :colspan="roles.length + 1" class="py-2 pl-4 pr-3 text-left text-sm font-bold text-gray-700 sm:pl-6 uppercase tracking-wider">
                      {{ moduleName }}
                    </td>
                  </tr>
                  <tr v-for="permission in modulePermissions" :key="permission.id" class="hover:bg-gray-50">
                    <td class="whitespace-nowrap py-2 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                      <div class="flex flex-col">
                        <span>{{ permission.display_name }}</span>
                        <span class="text-xs text-gray-500">{{ permission.description }}</span>
                      </div>
                    </td>
                    <td v-for="role in roles" :key="role.id" class="whitespace-nowrap px-3 py-2 text-center text-sm text-gray-500">
                      <input 
                        type="checkbox" 
                        :checked="hasPermission(role, permission.name)"
                        @change="togglePermission(role, permission.name)"
                        :disabled="role.name === 'admin' && permission.name === 'user.manage'"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                      />
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { api } from '@/bootstrap';

const roles = ref([]);
const permissions = ref({});
const loading = ref(true);
const saving = ref(false);
const pendingChanges = ref({}); // roleId -> Set of permission names

const hasChanges = computed(() => Object.keys(pendingChanges.value).length > 0);

onMounted(async () => {
    await loadData();
});

const loadData = async () => {
    try {
        const response = await api.get('/admin/roles');
        roles.value = response.data.roles;
        permissions.value = response.data.permissions;
    } catch (error) {
        console.error('Failed to load roles', error);
        // alert('Erreur lors du chargement des données.'); // Silent fail or user notification?
    } finally {
        loading.value = false;
    }
};

const hasPermission = (role, permissionName) => {
    // Check pending changes first
    if (pendingChanges.value[role.id]) {
       // If we modified this role, is the permission in the NEW set?
       // Wait, pendingChanges usually stores the whole new list or we track diffs?
       // Let's store the current LIVE state in roles.permissions, and 'pendingChanges' just tracks "dirty" roles?
       // Actually simpler: update the local state directly, and track which roles are dirty.
       // But wait, "Cancel" feature? 
       // Simplest: update roles directly.
    }
    return role.permissions.some(p => p.name === permissionName);
};

const togglePermission = (role, permissionName) => {
    const hasIt = hasPermission(role, permissionName);
    let newPerms = [...role.permissions];
    
    if (hasIt) {
        newPerms = newPerms.filter(p => p.name !== permissionName);
    } else {
        newPerms.push({ name: permissionName });
    }
    
    // Update local state
    const roleIndex = roles.value.findIndex(r => r.id === role.id);
    roles.value[roleIndex].permissions = newPerms;
    
    // Mark as pending
    pendingChanges.value[role.id] = newPerms.map(p => p.name);
};

const saveChanges = async () => {
    if (!hasChanges.value) return;
    
    saving.value = true;
    try {
        // Process each modified role
        const promises = Object.keys(pendingChanges.value).map(roleId => {
            return api.put(`/admin/roles/${roleId}`, {
                permissions: pendingChanges.value[roleId]
            });
        });
        
        await Promise.all(promises);
        
        pendingChanges.value = {};
        alert('Permissions mises à jour avec succès.');
        // Reload to get fresh data (and cleaned pivot data)
        await loadData();
        
    } catch (error) {
        console.error('Save failed', error);
        alert('Erreur lors de la sauvegarde.');
    } finally {
        saving.value = false;
    }
};
</script>
