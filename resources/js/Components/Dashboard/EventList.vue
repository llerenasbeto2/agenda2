<template>
  <div class="event-list-panel">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-xl font-semibold text-white flex items-center">
        <i class="fas fa-list mr-1.5 text-cyan-400"></i>
        {{ formattedSelectedDate }}
      </h3>
      <span 
        v-if="selectedEvents.length > 0"
        class="text-sm text-cyan-400 bg-cyan-400/20 px-3 py-1 rounded-full"
      >
        {{ selectedEvents.length }} evento{{ selectedEvents.length !== 1 ? 's' : '' }}
      </span>
    </div>

    <!-- Event List or No Events Message -->
    <div v-if="selectedEvents.length > 0">
      <!-- Compact Event List -->
      <div class="space-y-3 max-h-[500px] overflow-y-auto pr-2">
        <div 
          v-for="event in paginatedEvents" 
          :key="event.title + event.start_time + event.date" 
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

      <!-- Pagination -->
      <div v-if="totalEventPages > 1" class="flex justify-center items-center pt-3 mt-3 border-t border-white/20 space-x-2">
        <button
          @click="goToPage(currentEventPage - 1)"
          :disabled="currentEventPage === 0"
          class="px-2.5 py-1 text-sm text-white hover:text-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 hover:bg-white/10 rounded-md"
        >
          Anterior
        </button>
        <button
          v-for="page in totalEventPages"
          :key="page"
          @click="goToPage(page - 1)"
          :class="['px-2.5 py-1 text-sm rounded-md transition-all duration-200', currentEventPage === page - 1 ? 'bg-cyan-500 text-white' : 'text-white hover:bg-white/10']"
        >
          {{ page }}
        </button>
        <button
          @click="goToPage(currentEventPage + 1)"
          :disabled="currentEventPage === totalEventPages - 1"
          class="px-2.5 py-1 text-sm text-white hover:text-cyan-400 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 hover:bg-white/10 rounded-md"
        >
          Siguiente
        </button>
      </div>
    </div>

    <!-- No Events Message -->
    <div v-else class="text-center py-6">
      <i class="fas fa-calendar-times text-3xl text-gray-400 mb-2"></i>
      <p class="text-sm text-gray-300">No hay eventos este día.</p>
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue';

const props = defineProps({
  selectedDate: String,
  events: Array,
  currentEventPage: {
    type: Number,
    default: 0
  },
  eventsPerPage: {
    type: Number,
    default: 3
  },
  hasActiveFilters: Boolean
});

const emit = defineEmits([
  'update-event-page',
  'reset-filters',
  'view-event',
  'edit-event'
]);

// Events for the selected date
const selectedEvents = computed(() => {
  if (!props.selectedDate || !props.events || !Array.isArray(props.events)) return [];
  return props.events.filter(event => event.date === props.selectedDate);
});

// Pagination
const totalEventPages = computed(() => {
  return Math.ceil(selectedEvents.value.length / props.eventsPerPage);
});

const paginatedEvents = computed(() => {
  const start = props.currentEventPage * props.eventsPerPage;
  return selectedEvents.value.slice(start, start + props.eventsPerPage);
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

// Navigate to a specific page
const goToPage = (page) => {
  if (page >= 0 && page < totalEventPages.value) {
    emit('update-event-page', page);
  }
};

// Watch for changes in selectedDate and reset pagination
watch(() => props.selectedDate, () => {
  if (props.currentEventPage > 0) {
    emit('update-event-page', 0);
  }
});

// Watch for changes in events array and adjust pagination if needed
watch(() => selectedEvents.value.length, (newLength) => {
  const maxPage = Math.max(0, Math.ceil(newLength / props.eventsPerPage) - 1);
  if (props.currentEventPage > maxPage) {
    emit('update-event-page', Math.max(0, maxPage));
  }
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
}
</style>