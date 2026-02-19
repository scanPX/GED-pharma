<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="glass-header -mx-4 px-4 py-8 sm:-mx-8 sm:px-8 mb-8 border-b border-gray-200/50">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Configuration Système</h1>
          <p class="text-sm text-gray-500 mt-1">Paramètres globaux de conformité et de sécurité.</p>
        </div>
        <div class="flex items-center space-x-3">
          <button 
            @click="saveSettings" 
            :disabled="saving || loading"
            class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-100 transition-all micro-interaction disabled:opacity-50"
          >
            <CloudArrowUpIcon v-if="!saving" class="w-5 h-5 mr-2" />
            <ArrowPathIcon v-else class="w-5 h-5 mr-2 animate-spin" />
            {{ saving ? 'Enregistrement...' : 'Sauvegarder' }}
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-gray-500 font-medium">Chargement des paramètres...</p>
      </div>

      <div v-else class="space-y-8">
        <!-- Settings Groups -->
        <div v-for="(groupSettings, groupName) in settings" :key="groupName" class="premium-card overflow-hidden">
          <div class="px-8 py-5 bg-gray-50/50 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-lg font-black text-gray-900 capitalize tracking-tight flex items-center">
              <span class="w-2 h-6 bg-blue-600 rounded-full mr-4"></span>
              {{ groupName }}
            </h3>
            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ groupSettings.length }} paramètres</span>
          </div>
          
          <div class="p-8 space-y-8">
            <div v-for="setting in groupSettings" :key="setting.id" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start pb-8 last:pb-0 border-b last:border-0 border-gray-100">
              <div>
                <label class="block text-sm font-bold text-gray-900 mb-1">{{ setting.description || setting.key }}</label>
                <p class="text-xs text-gray-400 font-mono tracking-tight">{{ setting.key }}</p>
              </div>
              
              <div class="md:col-span-2">
                <div v-if="isTypeBoolean(setting.key)" class="flex items-center">
                  <button 
                    @click="toggleBoolean(setting)"
                    :class="[
                      'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2',
                      setting.value == '1' ? 'bg-blue-600' : 'bg-gray-200'
                    ]"
                  >
                    <span :class="[setting.value == '1' ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"></span>
                  </button>
                  <span class="ml-3 text-sm font-medium text-gray-600">{{ setting.value == '1' ? 'Activé' : 'Désactivé' }}</span>
                </div>
                
                <div v-else class="relative">
                  <input 
                    type="text" 
                    v-model="setting.value"
                    class="w-full px-5 py-3 bg-gray-50 border-2 border-gray-100 rounded-xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm font-medium" 
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Compliance Banner -->
        <div class="bg-amber-50 border border-amber-100 rounded-3xl p-8 flex items-start space-x-6">
          <div class="p-4 bg-white rounded-2xl shadow-sm">
            <ShieldCheckIcon class="w-8 h-8 text-amber-600" />
          </div>
          <div>
            <h4 class="text-xl font-black text-amber-900">Exigences GxP & 21 CFR Part 11</h4>
            <p class="text-amber-700 mt-2 leading-relaxed">
              Toute modification de la configuration système est enregistrée de manière immuable dans l'Audit Trail. 
              Certains paramètres critiques peuvent impacter la validité des signatures électroniques existantes.
            </p>
            <router-link to="/audit" class="mt-4 inline-flex items-center text-amber-800 font-bold hover:underline">
              Consulter le journal d'audit des paramètres →
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/bootstrap';
import { 
  CloudArrowUpIcon, 
  ArrowPathIcon,
  ShieldCheckIcon 
} from '@heroicons/vue/24/outline';

const settings = ref({});
const loading = ref(true);
const saving = ref(false);

onMounted(async () => {
    await loadSettings();
});

const loadSettings = async () => {
    try {
      const response = await api.get('/admin/settings');
      const raw = response.data.settings || {};
      const normalized = {};
      
      for (const groupName in raw) {
        const group = raw[groupName] || [];
        normalized[groupName] = Array.isArray(group) ? group : Object.values(group);
      }
      settings.value = normalized;
    } catch (error) {
        console.error('Failed to load settings', error);
    } finally {
        loading.value = false;
    }
};

function isTypeBoolean(key) {
  const boolKeys = [
    'audit.reason_required',
    'auth.pin_required',
    'system.maintenance_mode',
    'notification.email_enabled'
  ];
  return boolKeys.some(bk => key.includes(bk) || key.endsWith('enabled') || key.endsWith('required'));
}

function toggleBoolean(setting) {
  setting.value = setting.value == '1' ? '0' : '1';
}

const saveSettings = async () => {
    saving.value = true;
    try {
        const payload = [];
        for (const group in settings.value) {
            settings.value[group].forEach(s => {
                payload.push({ key: s.key, value: s.value });
            });
        }

        await api.post('/admin/settings', { settings: payload });
        alert('Configuration système mise à jour avec succès.');
    } catch (error) {
        console.error('Save failed', error);
        alert('Erreur lors de la sauvegarde : ' + (error.response?.data?.message || error.message));
    } finally {
        saving.value = false;
    }
};
</script>
