<script setup>
import { ref, computed } from 'vue';
import ReservationRow from './ReservationRow.vue';

const props = defineProps({
  reservations: {
    type: Array,
    required: true
  }
});

const currentPage = ref(1);
const itemsPerPage = 6;

const totalPages = computed(() => {
  return Math.ceil(props.reservations.length / itemsPerPage);
});

const paginatedReservations = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return props.reservations.slice(start, end);
});

const canGoPrevious = computed(() => currentPage.value > 1);
const canGoNext = computed(() => currentPage.value < totalPages.value);

const goToPreviousPage = () => {
  if (canGoPrevious.value) {
    currentPage.value--;
  }
};

const goToNextPage = () => {
  if (canGoNext.value) {
    currentPage.value++;
  }
};

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

// Reset current page when reservations change
const resetPage = () => {
  currentPage.value = 1;
};

// Watch for changes in reservations to reset page
import { watch } from 'vue';
watch(() => props.reservations.length, () => {
  if (currentPage.value > totalPages.value && totalPages.value > 0) {
    currentPage.value = totalPages.value;
  } else if (totalPages.value === 0) {
    currentPage.value = 1;
  }
});

const getVisiblePages = computed(() => {
  const pages = [];
  const maxVisible = 5;
  
  if (totalPages.value <= maxVisible) {
    for (let i = 1; i <= totalPages.value; i++) {
      pages.push(i);
    }
  } else {
    const start = Math.max(1, currentPage.value - 2);
    const end = Math.min(totalPages.value, start + maxVisible - 1);
    
    for (let i = start; i <= end; i++) {
      pages.push(i);
    }
  }
  
  return pages;
});
</script>

<template>
  <div class="overflow-hidden">
    <!-- Tabla -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gradient-to-r from-teal-50 to-teal-100 dark:from-teal-900 dark:to-teal-800">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-bold text-teal-700 dark:text-teal-300 uppercase tracking-wider">
              ID
            </th>
            <th class="px-6 py-4 text-left text-xs font-bold text-teal-700 dark:text-teal-300 uppercase tracking-wider">
              Evento
            </th>
            <th class="px-6 py-4 text-left text-xs font-bold text-teal-700 dark:text-teal-300 uppercase tracking-wider">
              Solicitante
            </th>
            <th class="px-6 py-4 text-left text-xs font-bold text-teal-700 dark:text-teal-300 uppercase tracking-wider">
              Ubicación
            </th>
            <th class="px-6 py-4 text-left text-xs font-bold text-teal-700 dark:text-teal-300 uppercase tracking-wider">
              Estatus
            </th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          <ReservationRow 
            v-for="reservation in paginatedReservations" 
            :key="reservation.id" 
            :reservation="reservation" 
            class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
          />
          
          <!-- Empty state -->
          <tr v-if="paginatedReservations.length === 0">
            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
              <div class="flex flex-col items-center">
                <i class="fi fi-rr-clipboard-list text-4xl text-gray-400 mb-4"></i>
                <p class="text-lg font-medium">No se encontraron reservaciones</p>
                <p class="text-sm">Intenta ajustar tus filtros de búsqueda</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Paginación -->
    <div v-if="totalPages > 1" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
      <div class="flex items-center justify-between">
        <!-- Información de página -->
        <div class="flex items-center text-sm text-gray-700 dark:text-gray-300">
          <span>
            Mostrando {{ (currentPage - 1) * itemsPerPage + 1 }} a 
            {{ Math.min(currentPage * itemsPerPage, reservations.length) }} 
            de {{ reservations.length }} resultados
          </span>
        </div>
        
        <!-- Controles de navegación -->
        <div class="flex items-center space-x-2">
          <!-- Botón anterior -->
          <button 
            @click="goToPreviousPage"
            :disabled="!canGoPrevious"
            :class="[
              'relative inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
              canGoPrevious 
                ? 'text-teal-600 bg-white border border-teal-300 hover:bg-teal-50 dark:bg-gray-700 dark:text-teal-400 dark:border-teal-600 dark:hover:bg-gray-600' 
                : 'text-gray-400 bg-gray-100 border border-gray-300 cursor-not-allowed dark:bg-gray-600 dark:text-gray-500 dark:border-gray-500'
            ]"
          >
            <i class="fi fi-rr-angle-left w-4 h-4 mr-1"></i>
            Anterior
          </button>
          
          <!-- Números de página -->
          <div class="hidden sm:flex space-x-1">
            <button
              v-for="page in getVisiblePages"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'relative inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
                page === currentPage
                  ? 'bg-teal-600 text-white border border-teal-600 dark:bg-teal-500'
                  : 'text-teal-600 bg-white border border-teal-300 hover:bg-teal-50 dark:bg-gray-700 dark:text-teal-400 dark:border-teal-600 dark:hover:bg-gray-600'
              ]"
            >
              {{ page }}
            </button>
          </div>
          
          <!-- Botón siguiente -->
          <button 
            @click="goToNextPage"
            :disabled="!canGoNext"
            :class="[
              'relative inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
              canGoNext 
                ? 'text-teal-600 bg-white border border-teal-300 hover:bg-teal-50 dark:bg-gray-700 dark:text-teal-400 dark:border-teal-600 dark:hover:bg-gray-600' 
                : 'text-gray-400 bg-gray-100 border border-gray-300 cursor-not-allowed dark:bg-gray-600 dark:text-gray-500 dark:border-gray-500'
            ]"
          >
            Siguiente
            <i class="fi fi-rr-angle-right w-4 h-4 ml-1"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>