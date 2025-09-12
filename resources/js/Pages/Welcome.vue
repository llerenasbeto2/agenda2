<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DynamicLayout from '@/Layouts/DynamicLayout.vue';
//import FacultyModal from '@/Components/FacultyModal.vue';
import EventCalendar from '@/Components/Dashboard/EventCalendar.vue';
import EventFilters from '@/Components/Dashboard/EventFilters.vue';
import EventList from '@/Components/Dashboard/EventList.vue';
import Leyenda from '@/Components/Leyenda.vue';

const props = defineProps({
  faculties: {
    type: Array,
    default: () => [],
  },
  events: {
    type: Array,
    default: () => [],
  },
  municipalities: {
    type: Array,
    default: () => [],
  },
  classrooms: {
    type: Array,
    default: () => [],
  },
});

const handleUpdateEventPage = (page) => {
  currentEventPage.value = page;
};
const selectedFaculty = ref(null);
const isModalOpen = ref(false);

// Estados de los filtros
const selectedMunicipality = ref('');
const selectedFacultyFilter = ref('');
const selectedClassroom = ref('');

// Estado del calendario y eventos
const selectedDate = ref(new Date().toISOString().split('T')[0]);
const currentEventPage = ref(0);
const eventsPerPage = 3;

// Verificar si hay filtros activos
const hasActiveFilters = computed(() => {
  return selectedMunicipality.value || selectedFacultyFilter.value || selectedClassroom.value;
});

// Eventos filtrados
const filteredEvents = computed(() => {
  if (!props.events?.length) return [];
  
  let filtered = [...props.events];
  
  // Filtrar por municipio
  if (selectedMunicipality.value) {
    filtered = filtered.filter(event => {
      const eventMunicipalityId = event.municipality_id;
      return Number(eventMunicipalityId) === Number(selectedMunicipality.value);
    });
  }
  
  // Filtrar por facultad
  if (selectedFacultyFilter.value) {
    const facultyId = Number(selectedFacultyFilter.value);
    filtered = filtered.filter(event => {
      const eventFacultyId = event.faculty_id;
      return Number(eventFacultyId) === facultyId;
    });
  }
  
  // Filtrar por aula
  if (selectedClassroom.value) {
    const classroomId = Number(selectedClassroom.value);
    filtered = filtered.filter(event => {
      const eventClassroomId = event.classroom_id;
      return Number(eventClassroomId) === classroomId;
    });
  }
  
  return filtered;
});

// Funciones de manejo
const handleResetFilters = () => {
  selectedMunicipality.value = '';
  selectedFacultyFilter.value = '';
  selectedClassroom.value = '';
  currentEventPage.value = 0;
};

const handleDateSelected = (date) => {
  selectedDate.value = date;
  currentEventPage.value = 0;
};

const handlePrevEventPage = () => {
  if (currentEventPage.value > 0) {
    currentEventPage.value--;
  }
};

const handleNextEventPage = () => {
  const totalPages = Math.ceil(filteredEvents.value.filter(event => event.date === selectedDate.value).length / eventsPerPage);
  if (currentEventPage.value < totalPages - 1) {
    currentEventPage.value++;
  }
};

const handleViewEvent = (event) => {
  console.log('Ver evento:', event);
  // Aquí puedes agregar la lógica para ver el evento
};

const handleEditEvent = (event) => {
  console.log('Editar evento:', event);
  // Aquí puedes agregar la lógica para editar el evento
};

const openModal = (faculty) => {
  selectedFaculty.value = faculty;
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedFaculty.value = null;
};
</script>

<template>
  <Head title="Dashboard" />

  <DynamicLayout>
    <div class="relative min-h-screen bg-space">
      <div class="container mx-auto py-8 px-4">
        <!-- Header -->
        <div class="text-center text-white mb-8" >
          <h1 class="text-4xl font-extrabold mb-4">Calendario de Eventos</h1>
          <p class="text-lg text-gray-300">Gestiona y consulta los eventos académicos.</p>
        </div>

        <!-- Layout principal según la imagen -->
        <div class="grid grid-cols-1 lg:grid-cols-2" style="margin-right: 200px;">
          
          <!-- Columna Izquierda: Calendario -->
          <div class="space-y-6">
            <EventCalendar
              :events="filteredEvents"
              :selectedMunicipality="selectedMunicipality"
              :selectedFaculty="selectedFacultyFilter"
              :selectedClassroom="selectedClassroom"
              :selectedDate="selectedDate"
              @date-selected="handleDateSelected"
              class="w-3/4 lg:w-2/3 -ml-2 lg:-ml-4"
            />
          </div>

          <!-- Columna Derecha: Filtros y Lista de Eventos -->
          <div class="space-y-6" style="width: 700px; height: 300px;">
            
            <!-- Filtros en la parte superior -->
            <EventFilters
              :municipalities="props.municipalities"
              :faculties="props.faculties"
              :classrooms="props.classrooms"
              v-model:selectedMunicipality="selectedMunicipality"
              v-model:selectedFaculty="selectedFacultyFilter"
              v-model:selectedClassroom="selectedClassroom"
              @reset-filters="handleResetFilters"
            />

            <!-- Lista de eventos en la parte inferior -->
            <div class="bg-gray-900/80 backdrop-blur-sm rounded-2xl border border-white/10 shadow-lg overflow-hidden">
              <div class="max-h-600 overflow-y-auto ">

                <EventList
                  :selectedDate="selectedDate"
                  :events="filteredEvents"
                  :currentEventPage="currentEventPage"
                  :eventsPerPage="eventsPerPage"
                  :hasActiveFilters="hasActiveFilters"
                  @update-event-page="handleUpdateEventPage"
                  @reset-filters="handleResetFilters"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

 
  </DynamicLayout>
  
  <Leyenda />
</template>

<style scoped>
/* Fondo con gradiente espacial (colores originales) */
.bg-space {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
  min-height: 100vh;
}

/* Efectos de glassmorphism para las tarjetas (colores originales) */
.bg-gray-50 {
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.bg-white {
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Animaciones para los elementos */
.grid > div {
  animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive mejoras */
@media (max-width: 1024px) {
  .lg\:grid-cols-2 {
    grid-template-columns: 1fr;
  }
  
  .space-y-6 > * + * {
    margin-top: 1.5rem;
  }
}

/* Efectos hover para elementos interactivos */
select:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
  transition: all 0.2s ease-in-out;
}

button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transition: all 0.2s ease-in-out;
}

/* Scrollbar personalizado para la lista de eventos */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Efectos de sombra para profundidad */
.shadow-sm {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Estilo mejorado para el container principal */
.container {
  max-width: 1400px;
}

/* Mejora visual para el header */
h1 {
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

/* Transiciones suaves para cambios de estado */
* {
  transition: all 0.2s ease-in-out;
}




</style>