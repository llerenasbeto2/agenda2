<template>
  <div class="event-list-panel">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-xl font-semibold text-white flex items-center">
        <i class="fas fa-list mr-1.5 text-cyan-400"></i>
        {{ formattedSelectedDate }}
      </h3>
      <input 
        v-model="searchQuery"
        type="text"
        placeholder="Buscar por título..."
        class="text-sm text-white bg-white/10 px-3 py-1 rounded-full border border-white/20 focus:outline-none focus:border-cyan-400 placeholder:text-gray-400 w-48"
      />
    </div>

    <!-- Event List or No Events Message -->
    <div v-if="filteredEvents.length > 0">
      <!-- Info de paginación -->
      <div class="text-xs text-gray-400 mb-2">
        Mostrando {{ startIndex + 1 }}-{{ endIndex }} de {{ filteredEvents.length }} eventos
      </div>

      <!-- Compact Event List -->
      <div class="space-y-3 max-h-[450px] overflow-y-auto pr-2">
        <div 
          v-for="(event, index) in paginatedEvents" 
          :key="`event-${props.selectedDate}-${startIndex + index}`" 
          class="event-card"
        >
          <!-- Main Event Info -->
          <div class="event-main">
            <div class="event-title-time">
              <h4 class="text-base font-medium text-white truncate">
                {{ event.title }}
              </h4>
              <div class="event-time-status">
                <span class="time-text">
                  <i class="fas fa-clock text-gray-400 text-xs mr-1"></i>
                  {{ event.start_time }} - {{ event.end_time }}
                </span>
                <span 
                  class="status-badge"
                  :class="getStatusClass(event.status || 'Aprobado')"
                >
                  {{ event.status || 'Aprobado' }}
                </span>
              </div>
            </div>
            <!-- Additional Details -->
            <div class="event-details">
              <div class="detail-item" v-if="event.classroom">
                <span class="detail-label">Aula:</span>
                <span class="detail-value">{{ event.classroom }}</span>
              </div>
              <div class="detail-item" v-if="event.faculty">
                <span class="detail-label">Facultad:</span>
                <span class="detail-value">{{ event.faculty }}</span>
              </div>
              <div class="detail-item" v-if="event.municipality">
                <span class="detail-label">Municipio:</span>
                <span class="detail-value">{{ event.municipality }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Tipo:</span>
                <span class="detail-value">{{ getEventType(event.title) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination Container -->
      <div class="flex justify-between items-center pt-6 mt-6 border-t border-white/20 space-x-2 min-h-[60px]">
        <div class="flex items-center space-x-2 text-sm text-white">
          <span>Mostrar</span>
          <select 
            v-model="localItemsPerPage" 
            @change="onItemsPerPageChange"
            class="text-sm text-white bg-white/10 px-2 py-1 rounded-md border border-white/20 focus:outline-none focus:border-cyan-400"
          >
            <option 
              v-for="option in perPageOptions" 
              :key="option" 
              :value="option"
            >
              {{ option }}
            </option>
          </select>
          <span>por página</span>
        </div>
        <template v-if="totalPages > 1">
          <div class="flex items-center space-x-2">
            <button
              @click="prevPage"
              :disabled="currentPage === 1"
              class="px-2.5 py-1 text-sm text-white hover:text-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 hover:bg-white/10 rounded-md"
            >
              Anterior
            </button>
            <button
              v-for="page in pageNumbers"
              :key="page"
              @click="goToPage(page)"
              :class="['px-2.5 py-1 text-sm rounded-md transition-all duration-200', currentPage === page ? 'bg-cyan-500 text-white' : 'text-white hover:bg-white/10']"
            >
              {{ page }}
            </button>
            <button
              @click="nextPage"
              :disabled="currentPage === totalPages"
              class="px-2.5 py-1 text-sm text-white hover:text-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 hover:bg-white/10 rounded-md"
            >
              Siguiente
            </button>
          </div>
        </template>
      </div>
    </div>

    <!-- No Events Message -->
    <div v-else class="text-center py-6">
      <i class="fas fa-calendar-times text-3xl text-gray-400 mb-2"></i>
      <p class="text-sm text-gray-300">
        {{ searchQuery ? 'No se encontraron eventos con ese título.' : 'No hay eventos este día.' }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  selectedDate: String,
  events: Array,
  itemsPerPage: {
    type: Number,
    default: 3
  },
  currentEventPage: {
    type: Number,
    default: 1
  },
  eventsPerPage: {
    type: Number,
    default: 3
  },
  hasActiveFilters: Boolean
});

const emit = defineEmits([
  'update:itemsPerPage',
  'page-changed',
  'update-event-page'
]);

// Estado local
const searchQuery = ref('');
const currentPage = ref(props.currentEventPage || 1);
const localItemsPerPage = ref(props.eventsPerPage || props.itemsPerPage);

const perPageOptions = [3, 5, 10, 25, 50];

// Events for the selected date
const selectedEvents = computed(() => {
  if (!props.selectedDate || !props.events || !Array.isArray(props.events)) return [];
  return props.events.filter(event => event.date === props.selectedDate);
});

// Filtered events based on search query
const filteredEvents = computed(() => {
  if (!searchQuery.value.trim()) return selectedEvents.value;
  const query = searchQuery.value.toLowerCase().trim();
  return selectedEvents.value.filter(event =>
    event.title.toLowerCase().includes(query)
  );
});

// Compute total pages
const totalPages = computed(() => {
  return Math.ceil(filteredEvents.value.length / localItemsPerPage.value);
});

// Calcular índices
const startIndex = computed(() => {
  return (currentPage.value - 1) * localItemsPerPage.value;
});

const endIndex = computed(() => {
  return Math.min(startIndex.value + localItemsPerPage.value, filteredEvents.value.length);
});

// Compute paginated items for the current page
const paginatedEvents = computed(() => {
  const start = startIndex.value;
  const end = endIndex.value;
  return filteredEvents.value.slice(start, end);
});

// Handle items per page change
const onItemsPerPageChange = () => {
  emit('update:itemsPerPage', localItemsPerPage.value);
  goToPage(1);
};

// Navigate to a specific page
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
    emit('update-event-page', page);  // Fixed: Match parent's listener
    emit('page-changed', paginatedEvents.value);
  }
};

// Previous page
const prevPage = () => {
  if (currentPage.value > 1) {
    goToPage(currentPage.value - 1);
  }
};

// Next page
const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    goToPage(currentPage.value + 1);
  }
};

// Generate page numbers for display
const pageNumbers = computed(() => {
  const pages = [];
  const maxVisiblePages = 5;
  let startPage = Math.max(1, currentPage.value - Math.floor(maxVisiblePages / 2));
  let endPage = Math.min(totalPages.value, startPage + maxVisiblePages - 1);

  if (endPage - startPage + 1 < maxVisiblePages) {
    startPage = Math.max(1, endPage - maxVisiblePages + 1);
  }

  for (let i = startPage; i <= endPage; i++) {
    pages.push(i);
  }

  return pages;
});

// Formatted date for display
const formattedSelectedDate = computed(() => {
  if (!props.selectedDate) return 'Selecciona una fecha';
  const [year, month, day] = props.selectedDate.split('-').map(Number);
  const date = new Date(year, month - 1, day);
  return `Agenda del ${date.toLocaleDateString('es-MX', { 
    weekday: 'long', 
    day: 'numeric',
    month: 'long', 
    year: 'numeric' 
  })}`;
});

// Status badge classes
const getStatusClass = (status) => {
  const statusClasses = {
    'Autorizado': 'status-authorized',
    'Pendiente': 'status-pending', 
    'Rechazado': 'status-rejected',
    'Confirmado': 'status-confirmed',
  };
  return statusClasses[status] || 'status-default';
};

// Determine event type
const getEventType = (title) => {
  if (!title) return 'Clase';
  const lowerTitle = title.toLowerCase();
  if (lowerTitle.includes('evaluación') || lowerTitle.includes('examen')) {
    return 'Examen';
  } else if (lowerTitle.includes('optativa') || lowerTitle.includes('clase')) {
    return 'Clase';
  }
  return 'Clase';
};

// Watch for changes in totalPages to reset to page 1 if needed
watch(totalPages, (newVal) => {
  if (currentPage.value > newVal) {
    goToPage(1);
  }
});

// Sync localItemsPerPage with props if parent changes it (fixed: watch both props)
watch(() => props.itemsPerPage, (newVal) => {
  localItemsPerPage.value = newVal;
});
watch(() => props.eventsPerPage, (newVal) => {
  localItemsPerPage.value = newVal;
});

// Sync currentPage with prop if parent changes it externally (new: for two-way binding robustness)
watch(() => props.currentEventPage, (newVal) => {
  if (newVal !== currentPage.value) {
    currentPage.value = newVal;
  }
});

// Watch for changes in selectedDate
watch(() => props.selectedDate, () => {
  searchQuery.value = '';
  goToPage(1);
});

// Watch for changes in searchQuery
watch(searchQuery, () => {
  goToPage(1);
});

// NEW: Watch for changes in selectedEvents (triggers on filter changes in parent)
watch(selectedEvents, () => {
  searchQuery.value = '';
  goToPage(1);
});
</script>

<style scoped>
.event-list-panel {
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(8px);
  border-radius: 16px;
  padding: 16px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2);
  width: 100%;
  max-width: 700px;
  min-height: 400px;
}

.event-card {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(8px);
  border-radius: 8px;
  padding: 10px 14px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-left: 3px solid rgba(34, 211, 238, 0.6);
  transition: all 0.2s ease;
}

.event-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  transform: translateY(-1px);
  border-left-color: rgba(34, 211, 238, 0.8);
}

.event-main {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.event-title-time {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}

.event-time-status {
  display: flex;
  align-items: center;
  gap: 10px;
}

.time-text {
  font-size: 11px;
  color: #d1d5db;
  font-weight: 500;
}

.status-badge {
  padding: 2px 8px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 500;
  text-transform: capitalize;
}

.status-authorized {
  background-color: rgba(34, 197, 94, 0.2);
  color: #4ade80;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-pending {
  background-color: rgba(245, 158, 11, 0.2);
  color: #fbbf24;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.status-rejected {
  background-color: rgba(239, 68, 68, 0.2);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.status-confirmed {
  background-color: rgba(59, 130, 246, 0.2);
  color: #60a5fa;
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.status-default {
  background-color: rgba(107, 114, 128, 0.2);
  color: #9ca3af;
  border: 1px solid rgba(107, 114, 128, 0.3);
}

.event-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
  gap: 6px 10px;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.detail-label {
  font-weight: 600;
  color: #e5e7eb;
  font-size: 11px;
  min-width: 45px;
}

.detail-value {
  color: #d1d5db;
  font-size: 11px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.overflow-y-auto::-webkit-scrollbar {
  width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: rgba(34, 211, 238, 0.5);
  border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: rgba(34, 211, 238, 0.8);
}

.space-y-3 > div {
  animation: slideInUp 0.3s ease-out;
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Estilos para las opciones del select */
select option {
  background-color: #000000;
  color: #ffffff;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .event-list-panel {
    padding: 12px;
  }

  .event-card {
    padding: 8px 12px;
  }

  .event-title-time {
    flex-direction: column;
    align-items: flex-start;
  }

  .event-time-status {
    width: 100%;
    justify-content: space-between;
  }

  .event-details {
    grid-template-columns: 1fr;
  }

  input[type="text"] {
    width: 100%;
  }

  .flex.justify-between {
    flex-direction: column;
    gap: 1rem;
  }

  .flex.items-center.space-x-2.text-sm.text-white {
    justify-content: center;
  }
}
</style>