<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
      <h2 class="text-xl font-semibold mb-6 text-gray-800 dark:text-gray-200">Definir Fechas</h2>
      
      <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300">Calendario</h3>
          <button 
            @click="openTimeModal(null)"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition-colors"
            :disabled="!form.classroom_id"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Agregar Fecha
          </button>
        </div>
        
        <!-- Calendar Component -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
          <div class="flex justify-between items-center mb-4">
            <button @click="prevMonth" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <h3 class="text-lg font-medium">{{ currentMonthName }} {{ currentYear }}</h3>
            <button @click="nextMonth" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
          
          <div class="grid grid-cols-7 gap-1 mb-2">
            <div v-for="day in ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']" 
                 :key="day" 
                 class="text-center font-medium text-sm text-gray-500 dark:text-gray-400">
              {{ day }}
            </div>
          </div>
          
          <div class="grid grid-cols-7 gap-1">
            <div v-for="day in daysInMonth" 
                 :key="day.date"
                 @click="selectDate(day)"
                 class="h-10 flex items-center justify-center rounded-full cursor-pointer text-sm"
                 :class="{
                   'text-gray-400 dark:text-gray-500': !day.isCurrentMonth,
                   'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200': isDateSelected(day.date),
                   'hover:bg-gray-200 dark:hover:bg-gray-600': day.isCurrentMonth && !isDateSelected(day.date),
                   'font-bold': day.isToday
                 }">
              {{ day.day }}
            </div>
          </div>
        </div>
        
        <!-- Selected Dates List -->
        <div v-if="selectedDates.length > 0" class="mb-6">
          <h3 class="text-lg font-medium mb-3 text-gray-700 dark:text-gray-300">Fechas Seleccionadas</h3>
          <div class="space-y-2">
            <div v-for="(date, index) in selectedDates" 
                 :key="index"
                 class="flex items-center justify-between p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
              <div>
                <span class="font-medium">{{ formatDate(date.start_date) }}</span>
                <span v-if="date.start_date !== date.end_date"> - {{ formatDate(date.end_date) }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                  {{ date.start_time }} - {{ date.end_time }}
                </span>
                <span v-if="date.is_recurring" class="ml-2 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-1 rounded">
                  {{ getRecurrenceLabel(date) }}
                </span>
              </div>
              <div class="flex space-x-2">
                <button @click="openTimeModal(index)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button @click="removeDate(index)" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Time Selection Modal -->
      <div v-if="showTimeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
          <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">
            {{ editingDateIndex !== null ? 'Editar Horario' : 'Agregar Horario' }}
          </h3>
          
          <div class="space-y-4">
          <!-- Date Display -->
            <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
              <span class="font-medium">{{ formatModalDate(modalDateToShow) }}</span>
            </div>
            
            <!-- Time Selection -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Hora de Inicio</label>
                <input 
                  type="time" 
                  v-model="timeForm.start_time" 
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
                  required
                />
              </div>
              <div>
                <label class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Hora de Fin</label>
                <input 
                  type="time" 
                  v-model="timeForm.end_time" 
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
                  required
                />
              </div>
            </div>
            
            <!-- Error Messages -->
            <div v-if="timeConflictError" class="text-red-500 text-sm mt-2">
              {{ timeConflictError }}
            </div>
            <div v-if="duplicateError" class="text-red-500 text-sm mt-2">
              {{ duplicateError }}
            </div>
            
            <!-- Recurrence Options -->
            <div class="mt-4">
              <div class="flex items-center mb-3">
                <input 
                  type="checkbox" 
                  id="isRecurring" 
                  v-model="timeForm.is_recurring" 
                  class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                />
                <label for="isRecurring" class="ml-2 font-medium text-gray-700 dark:text-gray-300">
                  Este evento se repite
                </label>
              </div>
              
              <div v-if="timeForm.is_recurring" class="pl-6 space-y-4">
                <div>
                  <label class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Frecuencia</label>
                  <select 
                    v-model="timeForm.recurring_frequency" 
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
                    required
                  >
                    <option value="daily">Diariamente</option>
                    <option value="weekly">Semanalmente</option>
                    <option value="monthly">Mensualmente</option>
                  </select>
                </div>
                
                <div v-if="timeForm.recurring_frequency === 'weekly'">
                  <label class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Días de la semana</label>
                  <div class="flex flex-wrap gap-2">
                    <button 
                      v-for="day in weekdays" 
                      :key="day.value"
                      @click="toggleRecurringDay(day.value)"
                      type="button"
                      class="px-3 py-1 rounded-full text-sm"
                      :class="{
                        'bg-blue-600 text-white': timeForm.recurring_days.includes(day.value),
                        'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300': !timeForm.recurring_days.includes(day.value)
                      }"
                    >
                      {{ day.label }}
                    </button>
                  </div>
                </div>
                
                <div>
                  <label class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Fecha de finalización</label>
            <input 
              type="date" 
              v-model="timeForm.recurring_end_date" 
              :min="modalDateToShow"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
              required
            />
                </div>
                
                <div>
                  <label class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Repeticiones</label>
                  <input 
                    type="number" 
                    v-model="timeForm.repeticion" 
                    min="1" 
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
                    required
                  />
                </div>
              </div>
            </div>
          </div>
          
          <div class="flex justify-end space-x-3 mt-6">
            <button 
              @click="showTimeModal = false" 
              class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            >
              Cancelar
            </button>
            <button 
              @click="saveTimeSelection" 
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition-colors"
              :disabled="!!timeConflictError || !!duplicateError"
            >
              Guardar
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, watch } from 'vue';
  
  const props = defineProps({
    form: Object,
    selectedDates: Array,
    showTimeModal: Boolean,
    editingDateIndex: [Number, null],
    timeForm: Object,
    timeConflictError: String,
    duplicateError: String,
    weekdays: Array,
    modalDateToShow: String
  });
  
  const emits = defineEmits([
    'openTimeModal',
    'prevMonth',
    'nextMonth',
    'selectDate',
    'isDateSelected',
    'removeDate',
    'saveTimeSelection',
    'toggleRecurringDay',
    'formatDate',
    'formatModalDate',
    'getRecurrenceLabel'
  ]);
  
  const currentDate = ref(new Date());
  
  const currentYear = computed(() => currentDate.value.getFullYear());
  const currentMonth = computed(() => currentDate.value.getMonth());
  const currentMonthName = computed(() => {
    return currentDate.value.toLocaleString('es-ES', { month: 'long' });
  });
  
  const daysInMonth = computed(() => {
    const year = currentYear.value;
    const month = currentMonth.value;
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const days = [];
  
    // Adjust firstDay to make Sunday (0) as 7 for correct grid alignment
    const adjustedFirstDay = firstDay === 0 ? 7 : firstDay;
  
    for (let i = 1; i < adjustedFirstDay; i++) {
      days.push({ day: '', isCurrentMonth: false });
    }
  
    for (let i = 1; i <= daysInMonth; i++) {
      const date = new Date(year, month, i);
      // Format date as yyyy-mm-dd in local time without timezone shift
      const localDateString = date.getFullYear() + '-' +
        String(date.getMonth() + 1).padStart(2, '0') + '-' +
        String(date.getDate()).padStart(2, '0');
      days.push({
        day: i,
        date: localDateString,
        isCurrentMonth: true,
        isToday: date.toDateString() === new Date().toDateString()
      });
    }
  
    return days;
  });
  
  // Watch modalDateToShow prop to update currentDate accordingly
  watch(() => props.modalDateToShow, (newVal) => {
    if (newVal) {
      const parts = newVal.split('-');
      if (parts.length === 3) {
        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        currentDate.value = new Date(year, month, 1);
      }
    }
  }, { immediate: true });
  
  </script>
  