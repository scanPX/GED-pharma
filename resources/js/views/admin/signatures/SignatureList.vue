<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="glass-header -mx-4 px-4 py-8 sm:-mx-8 sm:px-8 mb-8 border-b border-gray-200/50">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Registre des Signatures</h1>
        <p class="text-sm text-gray-500 mt-1">Registre immuable des signatures électroniques conformes 21 CFR Part 11.</p>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Search & Filters -->
      <div class="premium-card p-8 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="relative">
            <MagnifyingGlassIcon class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
            <input 
              v-model="filters.search"
              type="text" 
              placeholder="Rechercher un signataire..."
              class="w-full pl-12 pr-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm"
            >
          </div>
          
          <select v-model="filters.is_revoked" class="bg-gray-50 border-2 border-gray-100 rounded-2xl px-4 py-3 text-sm focus:border-blue-600 outline-none">
            <option :value="null">Toutes les signatures</option>
            <option :value="false">Valides uniquement</option>
            <option :value="true">Révoquées uniquement</option>
          </select>

          <div class="flex items-center space-x-3">
            <button @click="signatureStore.fetchSignatures(filters)" class="flex-1 bg-blue-600 text-white font-bold py-3 rounded-2xl hover:bg-blue-700 transition-all micro-interaction shadow-lg shadow-blue-100">
              Filtrer
            </button>
            <button @click="resetFilters" class="p-3 bg-gray-50 hover:bg-gray-100 rounded-2xl transition-colors">
              <ArrowPathIcon class="w-5 h-5 text-gray-500" />
            </button>
          </div>
        </div>
      </div>

      <!-- Signature Table -->
      <div class="premium-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                <th class="px-8 py-4">Signataire</th>
                <th class="px-8 py-4">Document / Entité</th>
                <th class="px-8 py-4">Signification</th>
                <th class="px-8 py-4">Statut</th>
                <th class="px-8 py-4">Date & IP</th>
                <th class="px-8 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-if="signatureStore.loading" v-for="i in 5" :key="i" class="animate-pulse">
                <td colspan="6" class="px-8 py-6 h-16 bg-gray-50/20"></td>
              </tr>
              <tr v-else v-for="sig in signatureStore.signatures" :key="sig.id" class="group hover:bg-gray-50/50 transition-colors">
                <td class="px-8 py-5">
                  <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center font-bold text-xs mr-3">
                      {{ sig.user_full_name?.charAt(0) }}
                    </div>
                    <div>
                      <p class="text-sm font-bold text-gray-900">{{ sig.user_full_name }}</p>
                      <p class="text-[10px] text-gray-400 uppercase tracking-wider">{{ sig.user_title }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-5">
                  <p class="text-sm font-bold text-gray-900 line-clamp-1">{{ sig.document?.title || sig.signable_type.split('\\').pop() }}</p>
                  <p class="text-[10px] text-gray-400 font-mono">{{ sig.document?.document_number || ('ID:' + sig.signable_id) }}</p>
                </td>
                <td class="px-8 py-5">
                  <span class="text-xs font-medium text-gray-600">{{ sig.meaning_description }}</span>
                </td>
                <td class="px-8 py-5">
                  <div class="flex items-center">
                    <span v-if="sig.is_revoked" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase bg-red-50 text-red-600 border border-red-100">
                      RÉVOQUÉE
                    </span>
                    <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-50 text-emerald-600 border border-emerald-100">
                      VALIDE
                    </span>
                  </div>
                </td>
                <td class="px-8 py-5">
                  <p class="text-xs text-gray-900">{{ formatDate(sig.signed_at) }}</p>
                  <p class="text-[10px] text-gray-400 font-mono">{{ sig.ip_address }}</p>
                </td>
                <td class="px-8 py-5 text-right">
                  <div class="flex items-center justify-end space-x-1">
                    <button 
                      @click="showVerifyModal(sig)"
                      class="p-2 hover:bg-white hover:text-blue-600 rounded-lg transition-all micro-interaction group-hover:shadow-sm"
                      title="Vérifier l'intégrité"
                    >
                      <ShieldCheckIcon class="w-5 h-5 text-gray-400 group-hover:text-blue-600" />
                    </button>
                    <button 
                      v-if="!sig.is_revoked"
                      @click="showRevokeModal(sig)"
                      class="p-2 hover:bg-white hover:text-red-600 rounded-lg transition-all micro-interaction group-hover:shadow-sm"
                      title="Révoquer"
                    >
                      <NoSymbolIcon class="w-5 h-5 text-gray-400 group-hover:text-red-600" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Verify Modal -->
    <div v-if="verificationResult" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="verificationResult = null"></div>
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden relative animate-scale-up">
        <div class="p-8">
          <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-black text-gray-900">Résultat de Vérification</h2>
            <div :class="verificationResult.is_valid ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'" class="p-3 rounded-2xl">
              <ShieldCheckIcon v-if="verificationResult.is_valid" class="w-8 h-8" />
              <ExclamationTriangleIcon v-else class="w-8 h-8" />
            </div>
          </div>

          <div class="space-y-4">
            <div v-for="(check, key) in verificationResult.checks" :key="key" class="p-4 rounded-2xl bg-gray-50 flex items-center justify-between">
              <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ key.replace('_', ' ') }}</p>
                <p class="text-sm font-bold text-gray-900">{{ check.message }}</p>
              </div>
              <CheckCircleIcon v-if="check.passed" class="w-6 h-6 text-emerald-500" />
              <XCircleIcon v-else class="w-6 h-6 text-red-500" />
            </div>
          </div>

          <p class="mt-8 text-xs text-gray-400 text-center uppercase tracking-widest font-black">
            Certificat généré le {{ new Date().toLocaleString() }}
          </p>
        </div>
      </div>
    </div>

    <!-- Revoke Modal -->
    <div v-if="revokeTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="revokeTarget = null"></div>
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden relative animate-scale-up">
        <div class="p-8">
          <h2 class="text-2xl font-black text-gray-900 mb-2">Révoquer la Signature</h2>
          <p class="text-sm text-gray-500 mb-6">Cette action est irréversible et sera enregistrée dans l'audit trail.</p>
          
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Motif de révocation</label>
              <textarea 
                v-model="revokeReason"
                rows="4" 
                class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-red-600 focus:bg-white transition-all outline-none text-sm"
                placeholder="Ex: Erreur de manipulation, Identité compromise..."
              ></textarea>
            </div>
            
            <div class="flex space-x-3">
              <button @click="revokeTarget = null" class="flex-1 py-4 bg-gray-100 text-gray-600 font-bold rounded-2xl hover:bg-gray-200 transition-all">Annuler</button>
              <button 
                @click="confirmRevoke" 
                :disabled="!revokeReason"
                class="flex-1 py-4 bg-red-600 text-white font-black rounded-2xl shadow-xl shadow-red-200 hover:bg-red-700 disabled:opacity-50 transition-all"
              >
                RÉVOQUER
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useSignatureStore } from '@/stores/signatures';
import { 
  MagnifyingGlassIcon, 
  ArrowPathIcon,
  ShieldCheckIcon,
  NoSymbolIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const signatureStore = useSignatureStore();
const filters = ref({
  search: '',
  is_revoked: null
});

const verificationResult = ref(null);
const revokeTarget = ref(null);
const revokeReason = ref('');

onMounted(() => {
  signatureStore.fetchSignatures();
});

function resetFilters() {
  filters.value = { search: '', is_revoked: null };
  signatureStore.fetchSignatures();
}

function formatDate(date) {
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

async function showVerifyModal(sig) {
  const res = await signatureStore.verifySignature(sig.id);
  verificationResult.value = res.data;
}

function showRevokeModal(sig) {
  revokeTarget.value = sig;
  revokeReason.value = '';
}

async function confirmRevoke() {
  if (!revokeTarget.value || !revokeReason.value) return;
  try {
    await signatureStore.revokeSignature(revokeTarget.value.id, revokeReason.value);
    revokeTarget.value = null;
    signatureStore.fetchSignatures(filters.value);
  } catch (err) {
    alert(err.message || "Erreur lors de la révocation");
  }
}
</script>
