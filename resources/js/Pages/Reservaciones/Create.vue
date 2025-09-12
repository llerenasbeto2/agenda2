<script setup>
import DynamicLayout from '@/Layouts/DynamicLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  userData: Object,
  formData: Object
});

// Estados y configuraciones
const stepLabels = ['Solicitante', 'Evento', 'Fechas', 'Términos'];
const currentStep = ref(1);
const termsAccepted = ref(false);
const classrooms = ref([]);
const selectedDates = ref([]);
const showTimeModal = ref(false);
const editingDateIndex = ref(null);
const timeConflictError = ref('');
const duplicateError = ref('');
const currentDate = ref(new Date());
const selectedDate = ref(new Date().toISOString().split('T')[0]);

// Configuración del formulario de tiempo
const timeForm = ref({
  start_time: '09:00',
  end_time: '10:00',
  is_recurring: false,
  recurring_frequency: 'weekly',
  recurring_days: [],
  recurring_end_date: '',
  repeticion: 1
});

// Días de la semana para eventos recurrentes
const weekdays = [
  { value: 'Monday', label: 'Lunes' },
  { value: 'Tuesday', label: 'Martes' },
  { value: 'Wednesday', label: 'Miércoles' },
  { value: 'Thursday', label: 'Jueves' },
  { value: 'Friday', label: 'Viernes' },
  { value: 'Saturday', label: 'Sábado' },
  { value: 'Sunday', label: 'Domingo' }
];

// Configuración del formulario principal (con Email en mayúscula)
const form = useForm({
  user_id: props.userData.id,
  full_name: props.userData.name || '',
  email: props.userData.email || '', 
  phone: props.userData.phone || '',
  event_title: '',
  category_type: '',
  faculty_id: '',
  classroom_id: '',
  attendees: 1,
  requirements: '',
  status: 'pendiente',
  is_recurring: false,
  recurring_frequency: null,
  recurring_days: null,
  recurring_end_date: null,
  repeticion: null
});

// Propiedades computadas del calendario
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

  for (let i = 0; i < firstDay; i++) {
    days.push({ day: '', isCurrentMonth: false });
  }

  for (let i = 1; i <= daysInMonth; i++) {
    const date = new Date(year, month, i);
    days.push({
      day: i,
      date: date.toISOString().split('T')[0],
      isCurrentMonth: true,
      isToday: date.toDateString() === new Date().toDateString()
    });
  }

  return days;
});

// Métodos del calendario
const prevMonth = () => {
  currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
};

const nextMonth = () => {
  currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
};

// Métodos de selección de fecha
const selectDate = (day) => {
  if (!day.isCurrentMonth || !form.classroom_id) return;
  selectedDate.value = day.date;
  openTimeModal(null);
};

const isDateSelected = (date) => {
  return selectedDates.value.some(d => d.start_date <= date && d.end_date >= date);
};

// Métodos del modal de tiempo
const openTimeModal = (index) => {
  if (!form.classroom_id) {
    alert('Por favor seleccione un aula primero');
    return;
  }

  editingDateIndex.value = index;
  
  if (index !== null) {
    const date = selectedDates.value[index];
    timeForm.value = {
      start_time: date.start_time,
      end_time: date.end_time,
      is_recurring: date.is_recurring,
      recurring_frequency: date.recurring_frequency || 'weekly',
      recurring_days: [...date.recurring_days] || [],
      recurring_end_date: date.recurring_end_date || selectedDate.value,
      repeticion: date.repeticion || 1
    };
  } else {
    timeForm.value = {
      start_time: '09:00',
      end_time: '10:00',
      is_recurring: false,
      recurring_frequency: 'weekly',
      recurring_days: [],
      recurring_end_date: selectedDate.value,
      repeticion: 1
    };
  }
  
  showTimeModal.value = true;
  timeConflictError.value = '';
  duplicateError.value = '';
};

const saveTimeSelection = () => {
  timeConflictError.value = '';
  duplicateError.value = '';

  if (timeForm.value.start_time >= timeForm.value.end_time) {
    timeConflictError.value = 'La hora de fin debe ser posterior a la de inicio';
    return;
  }

  if (timeForm.value.is_recurring && !timeForm.value.recurring_end_date) {
    timeConflictError.value = 'Debe especificar una fecha de finalización';
    return;
  }

  if (checkForDuplicateReservations()) {
    duplicateError.value = 'Ya existe una reserva para este horario';
    return;
  }

  if (new Date(timeForm.value.end_time) <= new Date(timeForm.value.start_time)) {
  timeConflictError.value = 'La hora de fin debe ser posterior a la de inicio';
  return;
  }

  const dateData = {
    start_date: selectedDate.value,
    end_date: timeForm.value.is_recurring ? timeForm.value.recurring_end_date : selectedDate.value,
    start_time: timeForm.value.start_time,
    end_time: timeForm.value.end_time,
    is_recurring: timeForm.value.is_recurring,
    recurring_frequency: timeForm.value.recurring_frequency,
    recurring_days: [...timeForm.value.recurring_days],
    recurring_end_date: timeForm.value.recurring_end_date,
    repeticion: timeForm.value.repeticion
  };

  if (editingDateIndex.value !== null) {
    selectedDates.value[editingDateIndex.value] = dateData;
  } else {
    selectedDates.value.push(dateData);
  }

  showTimeModal.value = false;
};

const checkForDuplicateReservations = () => {
  if (!form.classroom_id || !selectedDate.value) return false;

  const newStart = new Date(`${selectedDate.value}T${timeForm.value.start_time}`);
  const newEnd = new Date(`${selectedDate.value}T${timeForm.value.end_time}`);

  return selectedDates.value.some((date, index) => {
    if (editingDateIndex.value !== null && index === editingDateIndex.value) {
      return false;
    }

    const existingStart = new Date(`${date.start_date}T${date.start_time}`);
    const existingEnd = new Date(`${date.end_date}T${date.end_time}`);

    return (
      (newStart >= existingStart && newStart < existingEnd) ||
      (newEnd > existingStart && newEnd <= existingEnd) ||
      (newStart <= existingStart && newEnd >= existingEnd)
    );
  });
};

const removeDate = (index) => {
  selectedDates.value.splice(index, 1);
};

// Métodos de navegación
const nextStep = () => {
  if (validateCurrentStep()) {
    currentStep.value++;
  }
};

const prevStep = () => {
  currentStep.value--;
};

const goToStep = (step) => {
  if (step < currentStep.value) {
    currentStep.value = step;
  }
};

// Validación del formulario
const validateCurrentStep = () => {
  switch (currentStep.value) {
    case 1:
      if (!form.full_name || !form.full_name.trim()) {
        form.setError('full_name', 'Nombre completo requerido');
        return false;
      }
      if (!form.email || !form.email.trim()) {
        form.setError('Email', 'Email requerido'); // Usando Email con mayúscula
        return false;
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
        form.setError('Email', 'Email inválido');
        return false;
      }
      if (!form.phone || !form.phone.trim()) {
        form.setError('phone', 'Teléfono requerido');
        return false;
      }
      break;
    case 2:
      if (!form.event_title || !form.category_type || !form.faculty_id || !form.classroom_id || !form.attendees) {
        alert('Complete toda la información del evento');
        return false;
      }
      break;
    case 3:
      if (selectedDates.value.length === 0) {
        alert('Seleccione al menos una fecha');
        return false;
      }
      break;
  }
  return true;
};

// Carga de aulas
const loadClassrooms = async () => {
  if (!form.faculty_id) {
    classrooms.value = [];
    return;
  }

  try {
    const response = await axios.get(`/api/faculties/${form.faculty_id}/classrooms`);
    classrooms.value = response.data.data || [];
  } catch (error) {
    console.error('Error al cargar aulas:', error);
    classrooms.value = [];
  }
};

// Envío del formulario
const submit = async () => {
  if (!form.email || !form.email.trim()) {
    form.setError('email', 'Email es requerido');
    return;
  }

  if (!termsAccepted.value) {
    alert('Acepte los términos y condiciones');
    return;
  }

  if (selectedDates.value.length === 0) {
    alert('Seleccione al menos una fecha');
    return;
  }

  const mainDate = selectedDates.value[0];
  const payload = {
    user_id: form.user_id,
    full_name: form.full_name,
    email: form.email, // Asegúrate de usar minúscula
    phone: form.phone,
    faculty_id: form.faculty_id,
    municipality_id: 1, // id 1 para prueba, modificar después
    classroom_id: form.classroom_id,
    event_title: form.event_title,
    category_type: form.category_type,
    attendees: form.attendees,
    requirements: form.requirements,
    status: 'pendiente',
    start_datetime: `${mainDate.start_date}T${mainDate.start_time}:00`,
    end_datetime: `${mainDate.end_date}T${mainDate.end_time}:00`,
    is_recurring: selectedDates.value.some(date => date.is_recurring),
  };

  // Solo añadir campos de recurrencia si es necesario
  if (payload.is_recurring) {
    payload.recurring_frequency = mainDate.recurring_frequency;
    payload.recurring_days = mainDate.recurring_days;
    payload.recurring_end_date = mainDate.recurring_end_date;
    payload.repeticion = mainDate.repeticion;
  }

  console.log('Payload completo:', payload);

  try {
    const response = await axios.post('/reservations/classrooms', payload, {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    });

    if (response.data.success) {
      console.log('Reserva creada exitosamente:', response.data);
      alert('¡Reserva creada exitosamente!');
      window.location.href = '/misReservaciones';
    }
  } catch (error) {
    console.error('Error completo:', error.response?.data || error.message);
    
    if (error.response?.data?.errors) {
      const errors = error.response.data.errors;
      let errorMessages = [];
      
      for (const [field, messages] of Object.entries(errors)) {
        errorMessages.push(`${field}: ${messages.join(', ')}`);
      }
      
      alert(`Errores de validación:\n${errorMessages.join('\n')}`);
    } else {
      alert('Error al crear la reserva: ' + (error.response?.data?.message || error.message));
    }
  }
};

// Funciones auxiliares
const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString('es-ES', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });
};

const formatModalDate = (dateString) => {
  return formatDate(dateString);
};

const getRecurrenceLabel = (date) => {
  if (!date.is_recurring) return '';
  
  if (date.recurring_frequency === 'daily') {
    return `Diario (${date.repeticion}x)`;
  } else if (date.recurring_frequency === 'weekly') {
    const days = date.recurring_days.map(d => 
      weekdays.find(w => w.value === d)?.label.substring(0, 3)
    ).join(', ');
    return `Semanal (${days})`;
  }
  return `Mensual (${date.repeticion}x)`;
};

const toggleRecurringDay = (day) => {
  const index = timeForm.value.recurring_days.indexOf(day);
  if (index === -1) {
    timeForm.value.recurring_days.push(day);
  } else {
    timeForm.value.recurring_days.splice(index, 1);
  }
};

// Inicialización
onMounted(() => {
  if (props.userData) {
    form.full_name = props.userData.name || '';
    form.email = props.userData.email || ''; // Cargando con mayúscula
    form.phone = props.userData.phone || '';
  }
});

// Observadores
watch(() => form.faculty_id, loadClassrooms);

watch([() => timeForm.value.start_time, () => timeForm.value.end_time, () => form.classroom_id], () => {
  if (showTimeModal.value) {
    duplicateError.value = checkForDuplicateReservations() 
      ? 'Conflicto de horario' 
      : '';
  }
});
</script>

<template>
    <DynamicLayout>
      <Head title="Agendar Evento" />
      
      <div class="event-scheduling-container dark:bg-gray-900 min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4">
          <!-- Progress Steps -->
          <div class="flex justify-between mb-8 relative">
            <div class="absolute top-1/2 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 -z-10"></div>
            <div 
              class="absolute top-1/2 left-0 h-1 bg-blue-600 dark:bg-blue-500 transition-all duration-300 ease-in-out -z-10"
              :style="{ width: `${(currentStep - 1) * 33.33}%` }"
            ></div>
            
            <div 
              v-for="step in 4" 
              :key="step"
              @click="goToStep(step)"
              class="flex flex-col items-center cursor-pointer"
            >
              <div 
                class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold mb-2 transition-all"
                :class="{
                  'bg-blue-600 dark:bg-blue-500 scale-110': currentStep >= step,
                  'bg-gray-300 dark:bg-gray-600': currentStep < step
                }"
              >
                {{ step }}
              </div>
              <span 
                class="text-sm font-medium"
                :class="{
                  'text-blue-600 dark:text-blue-400': currentStep >= step,
                  'text-gray-500 dark:text-gray-400': currentStep < step
                }"
              >
                {{ stepLabels[step - 1] }}
              </span>
            </div>
          </div>
  
          <!-- Form Cards Carousel -->
          <div class="relative overflow-hidden">
            <div 
              class="flex transition-transform duration-300 ease-in-out"
              :style="{ transform: `translateX(-${(currentStep - 1) * 100}%)` }"
            >
              <!-- Card 1: Información del Solicitante -->
              <div class="w-full flex-shrink-0 px-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                  <h2 class="text-xl font-semibold mb-6 text-gray-800 dark:text-gray-200">Información del Solicitante</h2>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                      <label for="fullName" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Nombre Completo</label>
                      <input 
                        type="text" 
                        id="fullName" 
                        v-model="form.full_name" 
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
                        required
                      />
                      <div v-if="form.errors.full_name" class="text-red-500 text-sm mt-1">{{ form.errors.full_name }}</div>
                    </div>
                    
                    <div class="form-group">
                      <label for="email" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Correo Electrónico</label>
                      <input 
                        type="email" 
                        id="email" 
                        v-model="form.email" 
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
                        required
                      />
                      <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                    </div>
                    
                    <div class="form-group">
                      <label for="phone" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Teléfono</label>
                      <input 
                        type="tel" 
                        id="phone" 
                        v-model="form.phone" 
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
                        required
                      />
                      <div v-if="form.errors.phone" class="text-red-500 text-sm mt-1">{{ form.errors.phone }}</div>
                    </div>
                  </div>
                </div>
              </div>
  
              <!-- Card 2: Información del Evento -->
              <div class="w-full flex-shrink-0 px-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                  <h2 class="text-xl font-semibold mb-6 text-gray-800 dark:text-gray-200">Información del Evento</h2>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                      <label for="eventTitle" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Título del Evento</label>
                      <input 
                        type="text" 
                        id="eventTitle" 
                        v-model="form.event_title" 
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
                        required
                      />
                      <div v-if="form.errors.event_title" class="text-red-500 text-sm mt-1">{{ form.errors.event_title }}</div>
                    </div>
                    
                    <div class="form-group">
                      <label for="categoryType" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Categoría</label>
                      <select 
                        id="categoryType" 
                        v-model="form.category_type" 
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
                        required
                      >
                        <option value="" disabled>Seleccione una categoría</option>
                        <option v-for="category in formData.categories" :key="category.id" :value="category.id">
                          {{ category.name }}
                        </option>
                      </select>
                      <div v-if="form.errors.category_type" class="text-red-500 text-sm mt-1">{{ form.errors.category_type }}</div>
                    </div>
                    
                    <div class="form-group">
                      <label for="faculty" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Facultad</label>
                      <select 
                        id="faculty" 
                        v-model="form.faculty_id" 
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
                        required
                        @change="loadClassrooms"
                      >
                        <option value="" disabled>Seleccione una facultad</option>
                        <option v-for="faculty in formData.faculties" :key="faculty.id" :value="faculty.id">
                          {{ faculty.name }}
                        </option>
                      </select>
                      <div v-if="form.errors.faculty_id" class="text-red-500 text-sm mt-1">{{ form.errors.faculty_id }}</div>
                    </div>
                    
                    <div class="form-group">
                      <label for="classroom" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Aula</label>
                      <select 
                        id="classroom" 
                        v-model="form.classroom_id" 
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
                        required
                        :disabled="!form.faculty_id"
                      >
                        <option value="" disabled>Seleccione un aula</option>
                        <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">
                          {{ classroom.name }}
                        </option>
                      </select>
                      <div v-if="form.errors.classroom_id" class="text-red-500 text-sm mt-1">{{ form.errors.classroom_id }}</div>
                    </div>
                    
                    <div class="form-group">
                      <label for="attendees" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Número de Asistentes</label>
                      <input 
                        type="number" 
                        id="attendees" 
                        v-model="form.attendees" 
                        min="1" 
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
                        required
                      />
                      <div v-if="form.errors.attendees" class="text-red-500 text-sm mt-1">{{ form.errors.attendees }}</div>
                    </div>
                  </div>
                </div>
              </div>
  
              <!-- Card 3: Definir fecha -->
              <div class="w-full flex-shrink-0 px-2">
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
                </div>
              </div>
  
              <!-- Card 4: Términos y condiciones -->
              <div class="w-full flex-shrink-0 px-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                  <h2 class="text-xl font-semibold mb-6 text-gray-800 dark:text-gray-200">Términos y Condiciones</h2>
                  
                  <div class="mb-6">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 max-h-64 overflow-y-auto">
                      <h3 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Términos de Uso</h3>
                      <ol class="list-decimal list-inside space-y-2 text-gray-600 dark:text-gray-400">
                        <li>El solicitante es responsable del uso adecuado de las instalaciones.</li>
                        <li>Se debe respetar el horario establecido para el evento.</li>
                        <li>Cualquier daño ocasionado será responsabilidad del solicitante.</li>
                        <li>La cancelación debe realizarse con al menos 48 horas de anticipación.</li>
                        <li>El uso de equipos especializados requiere personal calificado.</li>
                      </ol>
                    </div>
                  </div>
                  
                  <div class="form-group">
                    <label for="requirements" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Requerimientos Especiales</label>
                    <textarea 
                      id="requirements" 
                      v-model="form.requirements" 
                      rows="4" 
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
                    ></textarea>
                    <div v-if="form.errors.requirements" class="text-red-500 text-sm mt-1">{{ form.errors.requirements }}</div>
                  </div>
                  
                  <div class="flex items-center mt-6">
                    <input 
                      id="termsCheckbox" 
                      type="checkbox" 
                      v-model="termsAccepted"
                      class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                      required
                    />
                    <label for="termsCheckbox" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                      Acepto los términos y condiciones
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
  
          <!-- Navigation Buttons -->
          <div class="flex justify-between mt-8">
            <button 
              type="button" 
              @click="prevStep"
              class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              :class="{ 'invisible': currentStep === 1 }"
            >
              Anterior
            </button>
            
            <button 
              v-if="currentStep < 4"
              type="button" 
              @click="nextStep"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition-colors"
            >
              Siguiente
            </button>
            
            <button 
              v-else
              type="button" 
              @click.prevent="submit"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition-colors"
              :disabled="form.processing || !termsAccepted"
              :class="{ 'opacity-50 cursor-not-allowed': form.processing || !termsAccepted }"
            >
              {{ form.processing ? 'Enviando...' : 'Enviar Solicitud' }}
            </button>
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
              <span class="font-medium">{{ formatModalDate(selectedDate) }}</span>
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
                    :min="selectedDate"
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
    </DynamicLayout>
  </template>
  
  <style scoped>
  .event-scheduling-container {
    background-color: #f9fafb;
  }
  .dark .event-scheduling-container {
    background-color: #111827;
  }
  
  .form-group {
    margin-bottom: 1.5rem;
  }
  
  .grid-cols-7 {
    grid-template-columns: repeat(7, minmax(0, 1fr));
  }
  
  .modal-enter-active, .modal-leave-active {
    transition: opacity 0.3s ease;
  }
  .modal-enter-from, .modal-leave-to {
    opacity: 0;
  }
  </style>
