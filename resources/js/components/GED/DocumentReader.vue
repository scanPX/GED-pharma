<template>
  <div class="document-reader flex flex-col h-full bg-gray-100 rounded-xl overflow-hidden border border-gray-200 shadow-sm">
    <!-- Toolbar -->
    <div class="reader-toolbar bg-white border-b border-gray-200 px-4 py-2 flex items-center justify-between sticky top-0 z-20">
      <div class="flex items-center space-x-2">
        <div class="flex items-center bg-gray-100 rounded-lg p-1">
          <button 
            @click="zoomOut" 
            :disabled="zoom <= 0.5"
            class="p-1.5 hover:bg-white hover:shadow-sm rounded-md disabled:opacity-30 transition-all"
            title="Zoom arrière"
          >
            <MagnifyingGlassMinusIcon class="w-5 h-5 text-gray-600" />
          </button>
          <span class="text-xs font-bold text-gray-500 w-12 text-center">{{ Math.round(zoom * 100) }}%</span>
          <button 
            @click="zoomIn" 
            :disabled="zoom >= 3.0"
            class="p-1.5 hover:bg-white hover:shadow-sm rounded-md disabled:opacity-30 transition-all"
            title="Zoom avant"
          >
            <MagnifyingGlassPlusIcon class="w-5 h-5 text-gray-600" />
          </button>
        </div>

        <div v-if="fileType === 'pdf'" class="flex items-center bg-gray-100 rounded-lg p-1 ml-2">
          <button 
            @click="prevPage" 
            :disabled="currentPage <= 1"
            class="p-1.5 hover:bg-white hover:shadow-sm rounded-md disabled:opacity-30 transition-all"
          >
            <ChevronLeftIcon class="w-5 h-5 text-gray-600" />
          </button>
          <span class="text-xs font-bold text-gray-500 px-2">{{ currentPage }} / {{ totalPages }}</span>
          <button 
            @click="nextPage" 
            :disabled="currentPage >= totalPages"
            class="p-1.5 hover:bg-white hover:shadow-sm rounded-md disabled:opacity-30 transition-all"
          >
            <ChevronRightIcon class="w-5 h-5 text-gray-600" />
          </button>
        </div>
      </div>

      <div class="flex items-center space-x-3">
        <!-- Search for PDF -->
        <div v-if="fileType === 'pdf'" class="relative hidden sm:block">
          <MagnifyingGlassIcon class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input 
            v-model="searchQuery"
            @keyup.enter="search"
            type="text" 
            placeholder="Rechercher..." 
            class="pl-9 pr-4 py-1.5 bg-gray-100 border-none rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all w-48"
          >
        </div>
        
        <button 
          @click="download"
          class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-all"
          title="Télécharger"
        >
          <ArrowDownTrayIcon class="w-5 h-5" />
        </button>
      </div>
    </div>

    <!-- Viewer Area -->
    <div 
      ref="viewerContainer"
      class="flex-1 overflow-auto p-4 sm:p-8 flex justify-center relative touch-pan-x touch-pan-y"
      @scroll="handleScroll"
    >
      <!-- Loading state -->
      <div v-if="loading" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-50/80 backdrop-blur-[2px] z-10">
        <div class="relative w-16 h-16">
          <div class="absolute inset-0 border-4 border-blue-100 rounded-full"></div>
          <div class="absolute inset-0 border-4 border-blue-600 rounded-full border-t-transparent animate-spin"></div>
        </div>
        <p class="mt-4 text-sm font-bold text-gray-500 animate-pulse">Chargement du document...</p>
      </div>

      <!-- PDF Canvas -->
      <div v-if="fileType === 'pdf'" class="pdf-container relative shadow-2xl bg-white">
        <canvas ref="pdfCanvas"></canvas>
      </div>

      <!-- DOCX Container -->
      <div v-else-if="fileType === 'docx'" ref="docxContainer" class="docx-container bg-white shadow-2xl p-8 sm:p-12 max-w-4xl w-full mx-auto min-h-screen">
      </div>

      <!-- TXT Container -->
      <div v-else-if="fileType === 'txt'" class="txt-container bg-white shadow-2xl p-8 sm:p-12 max-w-4xl w-full mx-auto font-mono text-sm leading-relaxed whitespace-pre-wrap text-gray-800">
        {{ txtContent }}
      </div>

      <!-- Unsupported / Error -->
      <div v-else-if="!loading" class="flex flex-col items-center justify-center py-20 text-center">
        <DocumentIcon class="w-16 h-16 text-gray-300 mb-4" />
        <h3 class="text-lg font-bold text-gray-900">Format non supporté en prévisualisation</h3>
        <p class="text-gray-500 mt-1 max-w-xs">Vous pouvez toujours télécharger le document pour le consulter avec vos applications locales.</p>
        <button 
          @click="download"
          class="mt-6 px-6 py-2 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-200"
        >
          Télécharger le fichier
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, onUnmounted } from 'vue';
import * as pdfjsLib from 'pdfjs-dist/legacy/build/pdf.mjs';
import api from '@/bootstrap';
import { renderAsync } from 'docx-preview';
import { 
  MagnifyingGlassMinusIcon, 
  MagnifyingGlassPlusIcon, 
  ChevronLeftIcon, 
  ChevronRightIcon,
  MagnifyingGlassIcon,
  ArrowDownTrayIcon,
  DocumentIcon
} from '@heroicons/vue/24/outline';

// Set worker src to local file from public folder
pdfjsLib.GlobalWorkerOptions.workerSrc = `/pdfjs/pdf.worker.mjs?t=${new Date().getTime()}`;

const props = defineProps({
  url: { type: String, required: true },
  extension: { type: String, required: true },
  fileName: { type: String, default: 'document' }
});

const emit = defineEmits(['download']);

const loading = ref(true);
const error = ref(null);
const fileType = ref('');
const zoom = ref(1.0);
const viewerContainer = ref(null);

// PDF specific
let pdfDoc = null;
const currentPage = ref(1);
const totalPages = ref(0);
const pdfCanvas = ref(null);
const searchQuery = ref('');

// DOCX & TXT specific
const docxContainer = ref(null);
const txtContent = ref('');

// Initialize based on extension
const detectFileType = () => {
  const ext = props.extension.toLowerCase();
  if (['pdf'].includes(ext)) return 'pdf';
  if (['docx', 'doc'].includes(ext)) return 'docx';
  if (['txt', 'log', 'sql', 'json'].includes(ext)) return 'txt';
  return 'unsupported';
};

const loadDocument = async () => {
  loading.value = true;
  fileType.value = detectFileType();
  
  try {
    // Ensure we handle URL relative to global api instance baseURL (/api/ged)
    let url = props.url;
    if (url.startsWith('/api/ged/')) {
        url = url.substring(8);
    } else if (url.startsWith('https://') || url.startsWith('http://')) {
        // If it's an absolute URL, find the /api/ged part
        const index = url.indexOf('/api/ged/');
        if (index !== -1) {
            url = url.substring(index + 8);
        }
    }

    const response = await api.get(url, {
      responseType: 'blob'
    });

    const blob = response.data;

    if (fileType.value === 'pdf') {
      await initPdf(blob);
    } else if (fileType.value === 'docx') {
      await initDocx(blob);
    } else if (fileType.value === 'txt') {
      txtContent.value = await blob.text();
    }
  } catch (err) {
    console.error('Reader Error:', err);
    
    // Attempt to extract error message from blob if necessary
    if (err.response?.data instanceof Blob && err.response.data.type === 'application/json') {
      const text = await err.response.data.text();
      try {
        const json = JSON.parse(text);
        error.value = json.message || 'Impossible de charger le document (Erreur serveur)';
      } catch (e) {
        error.value = 'Erreur lors de la lecture du document';
      }
    } else {
      error.value = err.response?.data?.message || err.message || 'Échec du chargement du document';
    }
  } finally {
    loading.value = false;
  }
};

// --- PDF Logic ---
const initPdf = async (blob) => {
  const arrayBuffer = await blob.arrayBuffer();
  pdfDoc = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
  totalPages.value = pdfDoc.numPages;
  await renderPage(1);
};

const renderPage = async (num) => {
  if (!pdfDoc || !pdfCanvas.value) return;
  
  const page = await pdfDoc.getPage(num);
  const viewport = page.getViewport({ scale: zoom.value * 2 }); // Render at 2x for clarity
  
  const canvas = pdfCanvas.value;
  const context = canvas.getContext('2d');
  canvas.height = viewport.height;
  canvas.width = viewport.width;

  // Set display size via CSS for zoom effect
  canvas.style.width = (viewport.width / 2) + 'px';
  canvas.style.height = (viewport.height / 2) + 'px';

  const renderContext = {
    canvasContext: context,
    viewport: viewport
  };
  
  await page.render(renderContext).promise;
  currentPage.value = num;
};

// --- DOCX Logic ---
const initDocx = async (blob) => {
  if (!docxContainer.value) return;
  await renderAsync(blob, docxContainer.value, docxContainer.value, {
    className: 'docx-preview',
    inWrapper: false
  });
};

// --- Actions ---
const zoomIn = () => {
  zoom.value = Math.min(zoom.value + 0.2, 3.0);
  if (fileType.value === 'pdf') renderPage(currentPage.value);
};

const zoomOut = () => {
  zoom.value = Math.max(zoom.value - 0.2, 0.5);
  if (fileType.value === 'pdf') renderPage(currentPage.value);
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    renderPage(currentPage.value + 1);
  }
};

const prevPage = () => {
  if (currentPage.value > 1) {
    renderPage(currentPage.value - 1);
  }
};

const download = () => {
  emit('download');
};

const handleScroll = (e) => {
  // Logic for continuous scrolling could be added here
};

const search = () => {
  // PDF Search logic using pdf.js text abstraction
  alert('Recherche: ' + searchQuery.value);
};

watch(() => props.url, loadDocument);

onMounted(loadDocument);
</script>

<style scoped>
.document-reader {
  font-family: 'Inter', sans-serif;
}

.pdf-container canvas {
  margin: 0 auto;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.docx-container :deep(.docx-wrapper) {
  background: transparent !important;
  padding: 0 !important;
}

.docx-container :deep(.docx) {
  margin-bottom: 0 !important;
  box-shadow: none !important;
}

/* Custom scrollbar */
.flex-1::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.flex-1::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.flex-1::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}

.flex-1::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
