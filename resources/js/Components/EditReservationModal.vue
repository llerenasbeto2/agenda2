<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  show: Boolean,
  reservation: Object,
  faculties: Array,
  classrooms: Array,
  categorie: Array,
  municipalities: Array
});

const emit = defineEmits(['close']);

// Debug props to verify data
onMounted(() => {
  console.log('Prop Faculties:', JSON.stringify(props.faculties, null, 2));
  console.log('Prop Classrooms:', JSON.stringify(props.classrooms, null, 2));
  console.log('Prop Municipalities:', JSON.stringify(props.municipalities, null, 2));
  console.log('Prop Reservation:', JSON.stringify(props.reservation, null, 2));
});

// Format ISO date for datetime-local input without timezone conversion
const formatDateTimeForInput = (dateTime) => {
  if (!dateTime) return '';
  try {
    return dateTime.slice(0, 16); // e.g., "2025-06-10T09:00"
  } catch (error) {
    console.error('Error formatting date:', dateTime, error);
    return '';
  }
};

// Time options for 30-minute intervals from 7:00 AM to 8:00 PM
const timeOptions = ref([
  '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
  '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30',
  '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30',
  '19:00', '19:30', '20:00'
]);

// Estados
const irregularDates = ref([]);
const newIrregularDate = ref({ date: '', startTime: '', endTime: '' });

// Estados para visualización de fechas
const displayStartDateTime = ref('');
const displayEndDateTime = ref('');

// Inicializar formulario con datos de la reservación
const form = useForm({
  id: props.reservation?.id || '',
  full_name: props.reservation?.full_name || '',
  Email: props.reservation?.Email || '',
  phone: props.reservation?.phone || '',
  faculty_id: props.reservation?.faculty_id ? String(props.reservation.faculty_id) : '',
  municipality_id: props.reservation?.municipality_id ? String(props.reservation.municipality_id) : '',
  classroom_id: props.reservation?.classroom_id ? String(props.reservation.classroom_id) : '',
  event_title: props.reservation?.event_title || '',
  category_type: props.reservation?.category_type || '',
  attendees: props.reservation?.attendees ? String(props.reservation.attendees) : '',
  start_datetime: props.reservation?.start_datetime || '',
  end_datetime: props.reservation?.end_datetime || '',
  requirements: props.reservation?.requirements || '',
  status: props.reservation?.status || 'Pendiente',
  irregular_dates: props.reservation?.irregular_dates || null
});

// Estado para controlar el proceso de guardado
const isSubmitting = ref(false);

// Inicializar valores de visualización y resetear formulario
const resetToOriginal = () => {
  displayStartDateTime.value = formatDateTimeForInput(props.reservation?.start_datetime);
  displayEndDateTime.value = formatDateTimeForInput(props.reservation?.end_datetime);
  irregularDates.value = props.reservation?.irregular_dates
    ? Array.isArray(props.reservation.irregular_dates)
      ? props.reservation.irregular_dates
      : JSON.parse(props.reservation.irregular_dates || '[]')
    : [];
  form.reset();
  form.clearErrors();
  isSubmitting.value = false;
};

onMounted(resetToOriginal);

// Sincronizar form solo cuando el usuario cambia los inputs
watch(displayStartDateTime, (newValue) => {
  if (newValue && newValue !== formatDateTimeForInput(props.reservation?.start_datetime)) {
    form.start_datetime = `${newValue}:00Z`;
  }
});
watch(displayEndDateTime, (newValue) => {
  if (newValue && newValue !== formatDateTimeForInput(props.reservation?.end_datetime)) {
    form.end_datetime = `${newValue}:00Z`;
  }
});

// Watch irregularDates to update displayText reactively
watch(irregularDates, (newDates) => {
  newDates.forEach((date) => {
    if (date.date && date.startTime && date.endTime) {
      date.displayText = `${date.date} ${date.startTime} - ${date.endTime}`;
    }
  });
}, { deep: true });

// Filtrar facultades según el municipio seleccionado
const filteredFaculties = computed(() => {
  if (!form.municipality_id) {
    console.log('No municipality selected, returning empty faculties');
    return [];
  }
  const filtered = props.faculties.filter(faculty => {
    if (!faculty || faculty.municipality_id == null) {
      console.log(`Faculty ${faculty?.name || 'unknown'}: Invalid or missing municipality_id (${faculty?.municipality_id})`);
      return false;
    }
    const matches = String(faculty.municipality_id) === String(form.municipality_id);
    console.log(`Faculty ${faculty.name}: municipality_id=${faculty.municipality_id}, form.municipality_id=${form.municipality_id}, matches=${matches}`);
    return matches;
  });
  console.log('Filtered Faculties:', JSON.stringify(filtered, null, 2));
  return filtered;
});

// Filtrar aulas según la facultad seleccionada
const filteredClassrooms = computed(() => {
  if (!form.faculty_id) {
    console.log('No faculty selected, returning empty classrooms');
    return [];
  }
  const filtered = props.classrooms.filter(classroom => {
    if (!classroom || classroom.faculty_id == null) {
      console.log(`Classroom ${classroom?.name || 'unknown'}: Invalid or missing faculty_id (${classroom?.faculty_id})`);
      return false;
    }
    const matches = String(classroom.faculty_id) === String(form.faculty_id);
    console.log(`Classroom ${classroom.name}: faculty_id=${classroom.faculty_id}, form.faculty_id=${form.faculty_id}, matches=${matches}`);
    return matches;
  });
  console.log('Filtered Classrooms:', JSON.stringify(filtered, null, 2));
  return filtered;
});

// Resetear faculty_id y classroom_id cuando cambie municipality_id
watch(() => form.municipality_id, (newMunicipalityId) => {
  console.log('Municipality changed to:', newMunicipalityId, 'Type:', typeof newMunicipalityId);
  form.faculty_id = '';
  form.classroom_id = '';
});

// Resetear classroom_id cuando cambie faculty_id
watch(() => form.faculty_id, (newFacultyId) => {
  console.log('Faculty changed to:', newFacultyId, 'Type:', typeof newFacultyId);
  form.classroom_id = '';
});

// Agregar fecha irregular
const addIrregularDate = () => {
  if (newIrregularDate.value.date && newIrregularDate.value.startTime && newIrregularDate.value.endTime) {
    irregularDates.value.push({
      date: newIrregularDate.value.date,
      startTime: newIrregularDate.value.startTime,
      endTime: newIrregularDate.value.endTime,
      displayText: `${newIrregularDate.value.date} ${newIrregularDate.value.startTime} - ${newIrregularDate.value.endTime}`
    });
    newIrregularDate.value = { date: '', startTime: '', endTime: '' };
  }
};

// Eliminar fecha irregular
const removeIrregularDate = (index) => {
  irregularDates.value.splice(index, 1);
  console.log('After removal, irregularDates:', JSON.stringify(irregularDates.value, null, 2));
};

// Preparar datos para envío
// Preparar datos para envío - VERSION CORREGIDA
const prepareSubmit = () => {
  // Manejar fechas irregulares
  form.irregular_dates = irregularDates.value.length > 0 ? JSON.stringify(irregularDates.value) : null;
  console.log('Prepared irregular_dates:', form.irregular_dates);
  
  // Solo actualizar las fechas si realmente han cambiado
  if (displayStartDateTime.value && displayStartDateTime.value !== formatDateTimeForInput(props.reservation?.start_datetime)) {
    form.start_datetime = `${displayStartDateTime.value}:00`;
  } else if (displayStartDateTime.value === '') {
    form.start_datetime = null;
  }
  
  if (displayEndDateTime.value && displayEndDateTime.value !== formatDateTimeForInput(props.reservation?.end_datetime)) {
    form.end_datetime = `${displayEndDateTime.value}:00`;
  } else if (displayEndDateTime.value === '') {
    form.end_datetime = null;
  }
  
  // Asegurar que los campos vacíos se conviertan a null
  Object.keys(form.data()).forEach(key => {
    if (form[key] === '' && key !== 'status') {
      form[key] = null;
    }
  });
};

// Enviar formulario
const submit = () => {
  if (!form.id) {
    console.error('Reservation ID is missing');
    return;
  }
  
  if (isSubmitting.value) return; // Evita múltiples envíos
  
  isSubmitting.value = true;
  prepareSubmit();
  
  const formData = new FormData();
  Object.entries(form.data()).forEach(([key, value]) => {
    formData.append(key, value === null ? '' : value);
  });
  
  console.log('FormData payload:', [...formData.entries()]);
  console.log('Submitting form with data:', JSON.stringify(form.data(), null, 2));
  
  form.put(route('admin.general.events.update', form.id), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      console.log('Form submitted successfully, irregular_dates:', form.irregular_dates);
      displayStartDateTime.value = formatDateTimeForInput(form.start_datetime);
      displayEndDateTime.value = formatDateTimeForInput(form.end_datetime);
      isSubmitting.value = false;
      emit('close');
    },
    onError: (errors) => {
      console.error('Form submission errors:', errors);
      displayStartDateTime.value = formatDateTimeForInput(form.start_datetime);
      displayEndDateTime.value = formatDateTimeForInput(form.end_datetime);
      isSubmitting.value = false;
    },
    onFinish: () => {
      isSubmitting.value = false;
    }
  });
};

// Cerrar modal y cancelar operación - CORREGIDO
const close = () => {
  // Si hay una operación en curso, cancelarla
  if (isSubmitting.value) {
    form.cancel(); // Cancela la solicitud HTTP activa
    console.log('Operación de guardado cancelada');
  }
  
  // Resetear estado
  isSubmitting.value = false;
  resetToOriginal();
  emit('close');
};
</script>

<template>
  <div v-if="show" class="fixed inset-0 overflow-y-auto z-50">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 transition-opacity" aria-hidden="true">
        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
      </div>

      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-200 mb-4">
            Editar Reservación
          </h3>

          <form @submit.prevent="submit">
            <!-- Información del solicitante -->
            <div class="mb-6">
              <h3 class="font-semibold text-lg mb-4 text-gray-800 dark:text-gray-200 border-b pb-2">
                Información del Solicitante
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre Completo</label>
                  <input id="full_name" v-model="form.full_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                  <div v-if="form.errors.full_name" class="text-red-500 text-sm mt-1">{{ form.errors.full_name }}</div>
                </div>
                <div>
                  <label for="Email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Correo Electrónico</label>
                  <input id="Email" v-model="form.Email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                  <div v-if="form.errors.Email" class="text-red-500 text-sm mt-1">{{ form.errors.Email }}</div>
                </div>
                <div>
                  <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
                  <input id="phone" v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                  <div v-if="form.errors.phone" class="text-red-500 text-sm mt-1">{{ form.errors.phone }}</div>
                </div>
                <div>
                  <label for="municipality_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Municipio</label>
                  <select id="municipality_id" v-model="form.municipality_id" disabled class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:cursor-not-allowed disabled:opacity-75">
                    <option value="">Seleccione un municipio</option>
                    <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">{{ municipality.name }}</option>
                  </select>
                  <div v-if="form.errors.municipality_id" class="text-red-500 text-sm mt-1">{{ form.errors.municipality_id }}</div>
                </div>
                <div>
                  <label for="faculty_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Facultad</label>
                  <select id="faculty_id" v-model="form.faculty_id" disabled class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:cursor-not-allowed disabled:opacity-75">
                    <option value="">Seleccione una facultad</option>
                    <option v-for="faculty in filteredFaculties" :key="faculty.id" :value="faculty.id">{{ faculty.name }}</option>
                  </select>
                  <div v-if="form.errors.faculty_id" class="text-red-500 text-sm mt-1">{{ form.errors.faculty_id }}</div>
                </div>
              </div>
            </div>

            <!-- Información del evento -->
            <div class="mb-6">
              <h3 class="font-semibold text-lg mb-4 text-gray-800 dark:text-gray-200 border-b pb-2">
                Información del Evento
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="event_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título del Evento</label>
                  <input id="event_title" v-model="form.event_title" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                  <div v-if="form.errors.event_title" class="text-red-500 text-sm mt-1">{{ form.errors.event_title }}</div>
                </div>
                <div>
                  <label for="category_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Evento</label>
                  <select id="category_type" v-model="form.category_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Seleccione una categoría</option>
                    <option v-for="category in categorie" :key="category.id" :value="category.id">{{ category.name }}</option>
                  </select>
                  <div v-if="form.errors.category_type" class="text-red-500 text-sm mt-1">{{ form.errors.category_type }}</div>
                </div>
                <div>
                  <label for="classroom_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Escenario/Aula</label>
                  <select id="classroom_id" v-model="form.classroom_id" disabled class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:cursor-not-allowed disabled:opacity-75">
                    <option value="">Seleccione un aula</option>
                    <option v-for="classroom in filteredClassrooms" :key="classroom.id" :value="classroom.id">{{ classroom.name }}</option>
                  </select>
                  <div v-if="form.errors.classroom_id" class="text-red-500 text-sm mt-1">{{ form.errors.classroom_id }}</div>
                </div>
                <div>
                  <label for="attendees" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número de Asistentes</label>
                  <input id="attendees" v-model="form.attendees" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                  <div v-if="form.errors.attendees" class="text-red-500 text-sm mt-1">{{ form.errors.attendees }}</div>
                </div>
                <div>
                  <label for="start_datetime" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha y Hora de Inicio</label>
                  <input id="start_datetime" v-model="displayStartDateTime" type="datetime-local" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                  <div v-if="form.errors.start_datetime" class="text-red-500 text-sm mt-1">{{ form.errors.start_datetime }}</div>
                </div>
                <div>
                  <label for="end_datetime" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha y Hora de Fin</label>
                  <input id="end_datetime" v-model="displayEndDateTime" type="datetime-local" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                  <div v-if="form.errors.end_datetime" class="text-red-500 text-sm mt-1">{{ form.errors.end_datetime }}</div>
                </div>
                <div>
                  <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                  <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                    <option value="Pendiente">Pendiente</option>
                    <option value="Aprobado">Aprobado</option>
                    <option value="Rechazado">Rechazado</option>
                    <option value="Cancelado">Cancelado</option>
                    <option value="No_realizado">No Realizado</option>
                    <option value="Realizado">Realizado</option>
                  </select>
                  <div v-if="form.errors.status" class="text-red-500 text-sm mt-1">{{ form.errors.status }}</div>
                </div>
              </div>
              <div class="mt-4">
                <label for="requirements" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Requerimientos</label>
                <textarea id="requirements" v-model="form.requirements" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                <div v-if="form.errors.requirements" class="text-red-500 text-sm mt-1">{{ form.errors.requirements }}</div>
              </div>
            </div>

            <!-- Opciones de recurrencia -->
            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <h3 class="font-semibold text-lg mb-4 text-gray-800 dark:text-gray-200 border-b pb-2">Opciones de Recurrencia</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fechas Irregulares</label>
                  <div class="flex flex-wrap gap-2 mb-4">
                    <input type="date" v-model="newIrregularDate.date" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                    <select v-model="newIrregularDate.startTime" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                      <option value="">Inicio</option>
                      <option v-for="time in timeOptions" :key="time" :value="time">{{ time }}</option>
                    </select>
                    <select v-model="newIrregularDate.endTime" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                      <option value="">Fin</option>
                      <option v-for="time in timeOptions" :key="time" :value="time">{{ time }}</option>
                    </select>
                    <button type="button" @click="addIrregularDate" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Agregar</button>
                  </div>
                  <div v-if="irregularDates.length" class="mt-4">
                    <table class="min-w-full bg-white dark:bg-gray-700 rounded-lg overflow-hidden">
                      <thead class="bg-gray-100 dark:bg-gray-600">
                        <tr>
                          <th class="px-4 py-2 text-left text-gray-700 dark:text-gray-200">Fecha</th>
                          <th class="px-4 py-2 text-left text-gray-700 dark:text-gray-200">Inicio</th>
                          <th class="px-4 py-2 text-left text-gray-700 dark:text-gray-200">Fin</th>
                          <th class="px-4 py-2 text-left text-gray-700 dark:text-gray-200">Acciones</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(date, index) in irregularDates" :key="index" class="border-b dark:border-gray-600">
                          <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                            <input type="date" v-model="date.date" class="bg-transparent border-b border-gray-300 dark:border-gray-500 focus:border-blue-500" />
                          </td>
                          <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                            <select v-model="date.startTime" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
           focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 
           dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                              <option v-for="time in timeOptions" :key="time" :value="time">{{ time }}</option>
                            </select>
                          </td>
                          <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                            <select v-model="date.endTime" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
           focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 
           dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                              <option v-for="time in timeOptions" :key="time" :value="time">{{ time }}</option>
                            </select>
                          </td>
                          <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                            <button type="button" @click="removeIrregularDate(index)" class="text-red-500 hover:text-red-700">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                              </svg>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <div v-if="form.errors.irregular_dates" class="text-red-500 text-sm mt-1">{{ form.errors.irregular_dates }}</div>
                </div>
              </div>
            </div>

            <!-- Botones de acción -->
            <div class="flex justify-end gap-4 mt-6">
              <button 
                type="button" 
                @click="close" 
                class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors"
              >
                Cancelar
              </button>
              <button 
                type="submit" 
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center" 
                :disabled="isSubmitting"
              >
                <span v-if="isSubmitting" class="animate-spin mr-2">
                  <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                  </svg>
                </span>
                {{ isSubmitting ? 'Guardando...' : 'Guardar Cambios' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>