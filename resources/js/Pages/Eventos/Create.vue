<script setup>
import DynamicLayout from '../../Layouts/DynamicLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import AtajoPasos from '@/Components/agendado/AtajoPasos.vue';
import classroom_faculties from '@/Components/agendado/classroom_faculties.vue';
import CalendarComponent from '@/Components/agendado/CalendarComponent.vue';
import Condiciones_requerimientos from '@/Components/agendado/Condiciones_requerimientos.vue';
import Expert_user from '@/Components/agendado/Expert_user.vue';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth.user);

const props = page.props;

const currentStep = ref(1);
const maxReached = ref(1);

// Estado persistente para los eventos del calendario
const persistedEvents = ref([]);

// Estado para el checkbox de términos
const termsAccepted = ref(false);

// Estado para controlar la visibilidad del modal
const showModal = ref(false);

// Estado para controlar la visibilidad del modal Expert_user
const showExpertModal = ref(false);

const form = useForm({
  user_id: props.userData ? props.userData.id : null,
  full_name: props.userData ? props.userData.name : '',
  email: props.userData ? props.userData.email : '',
  phone: props.userData ? props.userData.phone : '',
  faculty_id: null,
  municipality_id: '',
  classroom_id: null,
  event_title: '',
  category_type: '',
  attendees: '',
  start_datetime: null,
  end_datetime: null,
  requirements: '',
  status: 'Pendiente',
  is_recurring: false,
  repeticion: null,
  recurring_frequency: null,
  recurring_days: [], // Inicializar como array vacío
  recurring_end_date: null,
  irregular_dates: [], // Inicializar como array vacío
  cost: '0.00',
  is_paid: '0',
  payment_date: null,
});

const categories = ref(props.formData.categories || []);
const municipalities = ref(props.formData.municipalities || []);

const validateCurrentStep = () => {
  switch (currentStep.value) {
    case 1:
      return (
        form.full_name &&
        form.phone &&
        (isAuthenticated.value || form.email) &&
        form.event_title &&
        form.attendees &&
        form.category_type &&
        form.municipality_id
      );
    case 2:
      return form.faculty_id && form.classroom_id;
    case 3:
      return form.start_datetime && form.end_datetime;
    case 4:
      return termsAccepted.value; // Solo requiere que se acepten los términos
    default:
      return false;
  }
};

const nextStep = () => {
  if (validateCurrentStep()) {
    if (currentStep.value < 4) {
      currentStep.value++;
      maxReached.value = Math.max(maxReached.value, currentStep.value);
    }
  } else {
    alert('Por favor, completa todos los campos requeridos antes de avanzar.');
  }
};

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

const goToStep = (step) => {
  if (step <= maxReached.value) {
    currentStep.value = step;
  } else {
    alert('Por favor, completa los campos previos antes de avanzar a este paso.');
  }
};

const submit = () => {
  if (validateCurrentStep()) {
    form.post(route('myreservationsclassroom.store'), {
      onSuccess: () => {
        persistedEvents.value = [];
        alert('Reservación enviada con éxito!');
      },
      onError: (errors) => {
        console.error('Error al enviar:', errors);
        alert('Ocurrió un error al enviar la reservación. Revisa la consola para más detalles.');
      }
    });
  } else {
    alert('Debes aceptar los términos y condiciones.');
  }
};

// Función para manejar eventos del calendario
const handleCalendarEvents = (events) => {
  persistedEvents.value = events;
  console.log('Eventos persistidos actualizados:', persistedEvents.value);
};

// Actualizar form cuando el calendario emita eventos
const updateForm = (date, start, end, recurringData = {}) => {
  form.start_datetime = `${date} ${start}:00`;
  form.end_datetime = `${date} ${end}:00`;

  // Convertir irregular_dates a array si es un string JSON
  form.irregular_dates = typeof recurringData.irregular_dates === 'string'
    ? JSON.parse(recurringData.irregular_dates)
    : recurringData.irregular_dates || [];

  // Convertir recurring_days a array si es un string JSON
  form.recurring_days = typeof recurringData.recurring_days === 'string'
    ? JSON.parse(recurringData.recurring_days)
    : recurringData.recurring_days || [];

  form.is_recurring = recurringData.is_recurring || false;
  form.repeticion = recurringData.repeticion || null;
  form.recurring_frequency = recurringData.recurring_frequency || null;
  form.recurring_end_date = recurringData.recurring_end_date || null;

  console.log('Form actualizado con datos consolidados:', {
    is_recurring: form.is_recurring,
    repeticion: form.repeticion,
    recurring_frequency: form.recurring_frequency,
    recurring_days: form.recurring_days,
    irregular_dates_count: form.irregular_dates.length
  });
};

// Loguear info al cambiar de paso
watch(currentStep, (newStep, oldStep) => {
  console.log(`Cambiando de paso ${oldStep} a ${newStep}`);
  console.log('Información que se enviará a la base de datos:', {
    user_id: form.user_id,
    full_name: form.full_name,
    email: form.email,
    phone: form.phone,
    faculty_id: form.faculty_id,
    municipality_id: form.municipality_id,
    classroom_id: form.classroom_id,
    event_title: form.event_title,
    category_type: form.category_type,
    attendees: form.attendees,
    start_datetime: form.start_datetime,
    end_datetime: form.end_datetime,
    requirements: form.requirements,
    status: 'Pendiente',
    is_recurring: form.is_recurring,
    repeticion: form.repeticion,
    recurring_frequency: form.recurring_frequency,
    recurring_days: form.recurring_days,
    recurring_end_date: form.recurring_end_date,
    irregular_dates: form.irregular_dates,
    cost: '0.00',
    is_paid: '0',
    payment_date: null,
  });
});

// Resetear faculty_id, classroom_id y eventos del calendario al cambiar el municipio
watch(() => form.municipality_id, (newVal, oldVal) => {
  if (newVal !== oldVal && newVal !== '') {
    form.faculty_id = null;
    form.classroom_id = null;
    persistedEvents.value = [];
    form.irregular_dates = []; // Resetear irregular_dates
    form.recurring_days = []; // Resetear recurring_days
    console.log('Municipio cambiado. Reset de faculty_id, classroom_id y eventos del calendario.');
  }
});

// Resetear classroom_id y eventos del calendario al cambiar la facultad
watch(() => form.faculty_id, (newVal, oldVal) => {
  if (newVal !== oldVal && newVal !== null) {
    form.classroom_id = null;
    persistedEvents.value = [];
    form.irregular_dates = []; // Resetear irregular_dates
    form.recurring_days = []; // Resetear recurring_days
    console.log('Facultad cambiada. Reset de classroom_id y eventos del calendario.');
  }
});

// Resetear eventos del calendario al cambiar el aula
watch(() => form.classroom_id, (newVal, oldVal) => {
  if (newVal !== oldVal && newVal !== null) {
    persistedEvents.value = [];
    form.irregular_dates = []; // Resetear irregular_dates
    form.recurring_days = []; // Resetear recurring_days
    console.log('Aula cambiada. Reset de eventos del calendario.');
  }
});

watch(() => termsAccepted.value, (newVal) => {
  console.log('termsAccepted:', newVal);
});

watch(() => validateCurrentStep(), (newVal) => {
  console.log('validateCurrentStep:', newVal);
});
</script>

<template>
  <DynamicLayout>
    <Head title="Agendar Evento" />

    <!-- Atajo de pasos (stepper) -->
    <div class="max-w-2xl mx-auto mt-8">
      <AtajoPasos :current-step="currentStep" :max-reached="maxReached" :go-to-step="goToStep" />
    </div>
      
<!-- Create.vue -->
<!-- Texto clickeable para "Solicitud Directa" -->
<div class="text-center mb-4">
  <p class="text-sm text-gray-600 dark:text-gray-400">
    ¿Necesitas hacer una reserva rápida? 
    <a 
      href="#" 
      @click.prevent="showExpertModal = true" 
      class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 underline font-medium transition-colors duration-200"
    >
      Solicitud Directa
    </a>
  </p>
</div>
<!-- Modal Expert_user para Solicitud Directa -->
<Expert_user v-if="showExpertModal" :formData="props.formData" @close="showExpertModal = false" />

    <!-- Paso 1: Información personal -->
    <div v-if="currentStep === 1" class="max-w-2xl mx-auto p-6 bg-gray-50 dark:bg-gray-800 rounded-lg shadow-lg">
      <form class="space-y-4">
        <div>
          <label for="full_name" class="block text-sm font-medium text-gray-900 dark:text-gray-200">Nombre completo</label>
          <input
            id="full_name"
            v-model="form.full_name"
            type="text"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 sm:text-sm p-2"
          />
        </div>
        <div>
          <label for="phone" class="block text-sm font-medium text-gray-900 dark:text-gray-200">Teléfono</label>
          <input
            id="phone"
            v-model="form.phone"
            type="text"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 sm:text-sm p-2"
          />
        </div>
        <div>
          <label for="email" class="block text-sm font-medium text-gray-900 dark:text-gray-200">Correo electrónico</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 sm:text-sm p-2"
          />
        </div>
        <div>
          <label for="event_title" class="block text-sm font-medium text-gray-900 dark:text-gray-200">Título del evento</label>
          <input
            id="event_title"
            v-model="form.event_title"
            type="text"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 sm:text-sm p-2"
          />
        </div>
        <div>
          <label for="attendees" class="block text-sm font-medium text-gray-900 dark:text-gray-200">Número de participantes</label>
          <input
            id="attendees"
            v-model="form.attendees"
            type="number"
            min="1"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 sm:text-sm p-2"
          />
        </div>
        <div>
          <label for="category_type" class="block text-sm font-medium text-gray-900 dark:text-gray-200">Categoría</label>
          <select
            id="category_type"
            v-model="form.category_type"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 sm:text-sm p-2"
          >
            <option value="">Seleccione una categoría</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>
        <div>
          <label for="municipality_id" class="block text-sm font-medium text-gray-900 dark:text-gray-200">Municipio</label>
          <select
            id="municipality_id"
            v-model="form.municipality_id"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 sm:text-sm p-2"
          >
            <option value="">Seleccione un municipio</option>
            <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
              {{ municipality.name }}
            </option>
          </select>
        </div>
        <div class="flex justify-end">
          <button
            @click="nextStep"
            type="button"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            Siguiente
          </button>
        </div>
      </form>
    </div>

    <!-- Paso 2: Selección de Facultad y Salón -->
    <div v-if="currentStep === 2" class="max-w-4xl mx-auto p-6 bg-gray-50 dark:bg-gray-800 rounded-lg shadow-lg">
      <classroom_faculties
        :faculties="props.formData.faculties"
        :municipality-id="form.municipality_id"
        :initial-classrooms="props.formData.classrooms || []"
        :faculty-id="form.faculty_id"
        :classroom-id="form.classroom_id"
        @update:faculty-id="form.faculty_id = $event"
        @update:classroom-id="form.classroom_id = $event"
      />
      <div class="flex justify-between mt-4">
        <button
          @click="prevStep"
          type="button"
          class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500"
        >
          Anterior
        </button>
        <button
          @click="nextStep"
          type="button"
          class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          Siguiente
        </button>
      </div>
    </div>

    <!-- Paso 3: Calendario -->
    <div v-if="currentStep === 3" class="max-w-7xl mx-auto p-6 bg-gray-50 dark:bg-gray-800 rounded-lg shadow-lg">
      <CalendarComponent
        @update-form="updateForm"
        @events-updated="handleCalendarEvents"
        :initial-date="form.selected_date"
        :initial-start="form.start_time"
        :initial-end="form.end_time"
        :classroom-id="form.classroom_id"
        :persisted-events="persistedEvents"
      />
      <div class="flex justify-between mt-4">
        <button
          @click="prevStep"
          type="button"
          class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500"
        >
          Anterior
        </button>
        <button
          @click="nextStep"
          type="button"
          class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          Siguiente
        </button>
      </div>
    </div>

    <!-- Paso 4: Confirmación -->
    <div v-if="currentStep === 4" class="max-w-2xl mx-auto p-6 bg-gray-50 dark:bg-gray-800 rounded-lg shadow-lg">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200 mb-6">Confirmación de Reservación</h3>

      <!-- Requerimientos -->
      <div class="mb-6">
        <label for="requirements" class="block text-sm font-medium text-gray-900 dark:text-gray-200 mb-2">Requerimientos adicionales</label>
        <textarea
          id="requirements"
          v-model="form.requirements"
          rows="4"
          class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200"
          placeholder="Escribe aquí los requerimientos específicos para tu evento..."
        ></textarea>
      </div>

      <!-- Aceptación de términos con modal -->
      <div class="mb-6 flex items-center">
        <input
          id="terms"
          v-model="termsAccepted"
          type="checkbox"
          class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded"
        >
        <label for="terms" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
          Acepto los <a href="#" @click.prevent="showModal = true" class="text-indigo-600 hover:underline">términos y condiciones</a> de uso y política de reservas.
        </label>
      </div>

      <!-- Modal para términos y condiciones -->
      <Condiciones_requerimientos v-if="showModal" @close="showModal = false" />

      <!-- Botones de acción -->
      <div class="flex justify-between">
        <button
          @click="prevStep"
          type="button"
          class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500"
        >
          Anterior
        </button>
        <button
          @click="submit"
          type="button"
          class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          :disabled="!validateCurrentStep()"
        >
          Enviar Solicitud
        </button>
      </div>
    </div>
  </DynamicLayout>
</template>