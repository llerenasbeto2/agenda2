<template>
  <div class="calendar-panel">
    <!-- Header con navegación -->
    <div class="bg-gradient-to-r from-cyan-500 to-cyan-600 text-white px-6 py-4 rounded-t-2xl">
      <div class="flex justify-between items-center">
        <button 
          @click="prevMonth" 
          class="text-white hover:bg-white/20 rounded-full p-2 transition-all duration-200"
        ><-
          <i class="fas fa-chevron-left text-xl"></i>
        </button>
        
        <div class="text-center">
          <div class="text-3xl font-bold">{{ currentDay }}</div>
          <div class="text-sm opacity-90">{{ currentMonthName }}</div>
        </div>
        
        <button 
          @click="nextMonth" 
          class="text-white hover:bg-white/20 rounded-full p-2 transition-all duration-200"
        >->
          <i class="fas fa-chevron-right text-xl"></i>
        </button>
      </div>
    </div>

    <!-- Calendario -->
    <div class="bg-gray-900/80 backdrop-blur-sm rounded-b-2xl p-4 shadow-lg border border-white/10">
      <!-- Días de la semana -->
      <div class="grid grid-cols-7 gap-1 text-center mb-2">
        <div 
          v-for="day in daysShort" 
          :key="day" 
          class="font-medium text-cyan-300 py-2 text-sm"
        >
          {{ day }}
        </div>
      </div>

      <!-- Días del mes -->
      <div class="grid grid-cols-7 gap-1 text-center">
        <!-- Días en blanco del mes anterior -->
        <div 
          v-for="(blank, i) in blankDays" 
          :key="'blank-' + i" 
          class="h-10 flex items-center justify-center text-gray-500 text-sm"
        >
          {{ getPrevMonthDay(i) }}
        </div>

        <!-- Días del mes actual -->
        <div
          v-for="date in daysInMonth"
          :key="date"
          class="h-10 flex items-center justify-center rounded-lg cursor-pointer relative transition-all duration-200 text-sm font-medium"
          :class="{
            'bg-gradient-to-br from-cyan-400 to-cyan-500 text-black font-bold shadow-lg': hasFilteredEvent(date) && selectedDate !== formatDate(date),
            'bg-gradient-to-br from-cyan-600 to-cyan-700 text-white ring-2 ring-cyan-300': selectedDate === formatDate(date),
            'bg-gray-600/50 hover:bg-gray-500/70 text-white': !hasFilteredEvent(date) && selectedDate !== formatDate(date),
          }"
          @click="selectDate(date)"
        >
          {{ date }}
          <!-- Contador de eventos -->
          <span 
            v-if="getFilteredEventCount(date) > 0" 
            class="absolute -top-1 -right-1 bg-gradient-to-r from-red-500 to-red-600 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center font-bold "
            style="font-size: 9px;"
          >
            {{ getFilteredEventCount(date) }}
          </span>
        </div>

        <!-- Días del próximo mes -->
        <div 
          v-for="(nextDay, i) in nextMonthDays" 
          :key="'next-' + i" 
          class="h-10 flex items-center justify-center text-gray-500 text-sm"
        >
          {{ i + 1 }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, defineEmits } from 'vue';

const props = defineProps({
  events: Array,
  selectedMunicipality: [String, Number],
  selectedFaculty: [String, Number],
  selectedClassroom: [String, Number],
  selectedDate: String
});

const emit = defineEmits(['date-selected']);

const currentMonth = ref(new Date().getMonth());
const currentYear = ref(new Date().getFullYear());

const daysShort = ["D", "L", "M", "M", "J", "V", "S"];

// Día actual destacado (solo para mostrar en el header)
const currentDay = computed(() => {
  if (props.selectedDate) {
    const date = new Date(props.selectedDate);
    return date.getDate();
  }
  return new Date().getDate();
});

const currentMonthName = computed(() => {
  return new Date(currentYear.value, currentMonth.value).toLocaleDateString('es-MX', {
    month: 'long'
  });
});

const daysInMonth = computed(() => {
  return new Date(currentYear.value, currentMonth.value + 1, 0).getDate();
});

const blankDays = computed(() => {
  return new Array(new Date(currentYear.value, currentMonth.value, 1).getDay()).fill(null);
});

// Días del mes anterior para mostrar en gris
const getPrevMonthDay = (index) => {
  const prevMonth = currentMonth.value === 0 ? 11 : currentMonth.value - 1;
  const prevYear = currentMonth.value === 0 ? currentYear.value - 1 : currentYear.value;
  const daysInPrevMonth = new Date(prevYear, prevMonth + 1, 0).getDate();
  const firstDayOfWeek = new Date(currentYear.value, currentMonth.value, 1).getDay();
  return daysInPrevMonth - firstDayOfWeek + index + 1;
};

// Días del próximo mes
const nextMonthDays = computed(() => {
  const totalCells = 42; // 6 filas × 7 días
  const usedCells = blankDays.value.length + daysInMonth.value;
  return totalCells - usedCells;
});

const formatDate = (day) => {
  return `${currentYear.value}-${String(currentMonth.value + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
};

// Eventos filtrados según los filtros aplicados
const filteredEvents = computed(() => {
  if (!props.events?.length) return [];
  
  let filtered = [...props.events];
  
  // Filtrar por municipio
  if (props.selectedMunicipality) {
    filtered = filtered.filter(event => {
      const eventMunicipalityId = event.municipality_id;
      return Number(eventMunicipalityId) === Number(props.selectedMunicipality);
    });
  }
  
  // Filtrar por facultad
  if (props.selectedFaculty) {
    const facultyId = Number(props.selectedFaculty);
    filtered = filtered.filter(event => {
      const eventFacultyId = event.faculty_id;
      return Number(eventFacultyId) === facultyId;
    });
  }
  
  // Filtrar por aula
  if (props.selectedClassroom) {
    const classroomId = Number(props.selectedClassroom);
    filtered = filtered.filter(event => {
      const eventClassroomId = event.classroom_id;
      return Number(eventClassroomId) === classroomId;
    });
  }
  
  return filtered;
});

// Funciones del calendario usando eventos filtrados
const hasFilteredEvent = (day) => {
  const date = formatDate(day);
  return filteredEvents.value.some(event => event.date === date);
};

const getFilteredEventCount = (day) => {
  const date = formatDate(day);
  return filteredEvents.value.filter(event => event.date === date).length;
};

const selectDate = (day) => {
  const dateString = formatDate(day);
  emit('date-selected', dateString);
};

// Navegación del calendario
const prevMonth = () => {
  if (currentMonth.value === 0) {
    currentMonth.value = 11;
    currentYear.value--;
  } else {
    currentMonth.value--;
  }
};

const nextMonth = () => {
  if (currentMonth.value === 11) {
    currentMonth.value = 0;
    currentYear.value++;
  } else {
    currentMonth.value++;
  }
};

// Sincronizar el mes actual con la fecha seleccionada
watch(() => props.selectedDate, (newDate) => {
  if (newDate) {
    const date = new Date(newDate);
    currentMonth.value = date.getMonth();
    currentYear.value = date.getFullYear();
  }
});
</script>

<style scoped>
.calendar-panel {
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  border-radius: 16px;
  overflow: hidden;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Animaciones para los días */
.grid > div {
  transition: all 0.2s ease-in-out;
}

/* Efecto hover mejorado */
.cursor-pointer:hover {
  transform: translateY(-1px);
}

/* Día seleccionado con efecto especial */
.transform.scale-110 {
  z-index: 10;
  box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
}
</style>