<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  show: Boolean,
  reservation: Object,
  faculties: Array,
  classrooms: Array,
  categorie: Array,
  municipalities: Array,
  reservations: Array
});

const emit = defineEmits(['close']);

// Debug props to verify data
onMounted(() => {
  console.log('Prop Faculties:', JSON.stringify(props.faculties, null, 2));
  console.log('Prop Classrooms:', JSON.stringify(props.classrooms, null, 2));
  console.log('Prop Municipalities:', JSON.stringify(props.municipalities, null, 2));
  console.log('Prop Reservation:', JSON.stringify(props.reservation, null, 2));
  console.log('Prop Reservations:', JSON.stringify(props.reservations, null, 2));
});

// Time options for 30-minute intervals from 7:00 AM to 8:00 PM
const timeOptions = ref([
  '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
  '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30',
  '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30',
  '19:00', '19:30', '20:00'
]);

// Estados - ESTAS SON VARIABLES LOCALES, NO SE SINCRONIZAN AUTOMÁTICAMENTE
const irregularDates = ref([]);
const newIrregularDate = ref({ date: '', startTime: '', endTime: '' });

// Sistema de conflictos
const conflicts = ref([]);
const showConflicts = ref(false);

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

// Limpiar conflictos
const clearConflicts = () => {
  conflicts.value = [];
  showConflicts.value = false;
};

// Agregar conflicto
const addConflict = (conflict) => {
  // Verificar si ya existe el mismo conflicto
  const existingConflict = conflicts.value.find(c => 
    c.event_title === conflict.event_title && 
    c.date === conflict.date && 
    c.startTime === conflict.startTime && 
    c.endTime === conflict.endTime
  );
  
  if (!existingConflict) {
    conflicts.value.push(conflict);
    showConflicts.value = true;
  }
};

// Inicializar valores de visualización y resetear formulario
const resetToOriginal = () => {
  // Inicializar irregularDates desde los datos originales, incluyendo la fecha principal
  let rawIrregular = props.reservation?.irregular_dates;
  let parsedIrregular = [];
  if (rawIrregular) {
    if (Array.isArray(rawIrregular)) {
      parsedIrregular = [...rawIrregular];
    } else {
      try {
        parsedIrregular = JSON.parse(rawIrregular || '[]');
      } catch (e) {
        console.error('Error parsing irregular_dates:', rawIrregular, e);
        parsedIrregular = [];
      }
    }
  }

  // Agregar o marcar la fecha principal
  const startFull = props.reservation?.start_datetime;
  if (startFull) {
    const startDate = startFull.substring(0, 10);
    const startTime = startFull.substring(11, 16);
    const endFull = props.reservation?.end_datetime;
    const endTime = endFull ? endFull.substring(11, 16) : startTime;
    const mainDateObj = {
      date: startDate,
      startTime: startTime,
      endTime: endTime,
      isMain: true
    };
    updateDisplayText(mainDateObj);

    // Buscar si ya existe en parsedIrregular
    const index = parsedIrregular.findIndex(d =>
      d.date === mainDateObj.date &&
      d.startTime === mainDateObj.startTime &&
      d.endTime === mainDateObj.endTime
    );
    if (index !== -1) {
      // Marcar como principal y actualizar displayText
      parsedIrregular[index].isMain = true;
      parsedIrregular[index].displayText = mainDateObj.displayText;
    } else {
      // Agregar al inicio
      parsedIrregular.unshift(mainDateObj);
    }
  }

  irregularDates.value = parsedIrregular;

  // Actualizar displayText para las fechas irregulares cargadas (limpiar posibles valores incorrectos)
  irregularDates.value.forEach(dateObj => {
    if (!dateObj.displayText) {
      updateDisplayText(dateObj);
    }
  });
  
  // Limpiar conflictos
  clearConflicts();
  
  form.reset();
  form.clearErrors();
  isSubmitting.value = false;
};

onMounted(resetToOriginal);

// Solo actualizamos displayText cuando sea necesario, sin watchers automáticos
const updateDisplayText = (dateObj) => {
  if (dateObj.date && dateObj.startTime && dateObj.endTime) {
    dateObj.displayText = `${dateObj.date} ${dateObj.startTime} - ${dateObj.endTime}`;
  }
};

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

// Función para verificar conflictos internos en irregular_dates de la misma reservación
const checkInternalConflicts = (newDateObj) => {
  const newStart = new Date(`${newDateObj.date}T${newDateObj.startTime}:00`);
  const newEnd = new Date(`${newDateObj.date}T${newDateObj.endTime}:00`);
  const foundConflicts = [];

  // Verificar conflictos con las fechas irregulares ya agregadas
  irregularDates.value.forEach(existingDate => {
    // Solo verificar si es la misma fecha
    if (existingDate.date !== newDateObj.date) return;
    
    const existingStart = new Date(`${existingDate.date}T${existingDate.startTime}:00`);
    const existingEnd = new Date(`${existingDate.date}T${existingDate.endTime}:00`);

    // Solapamiento: si newStart < existingEnd y newEnd > existingStart
    if (newStart < existingEnd && newEnd > existingStart) {
      foundConflicts.push({
        event_title: 'Esta misma reservación',
        date: existingDate.date,
        startTime: existingDate.startTime,
        endTime: existingDate.endTime,
        newDate: newDateObj.date,
        newStartTime: newDateObj.startTime,
        newEndTime: newDateObj.endTime,
        isInternal: true
      });
    }
  });

  return foundConflicts;
};

// Función para verificar conflictos en irregular_dates de otras reservaciones aprobadas
const checkIrregularDateConflicts = (newDateObj) => {
  const newStart = new Date(`${newDateObj.date}T${newDateObj.startTime}:00`);
  const newEnd = new Date(`${newDateObj.date}T${newDateObj.endTime}:00`);
  const foundConflicts = [];

  props.reservations.forEach(res => {
    // Excluir la reservación actual
    if (res.id === props.reservation?.id) return;
    
    // Filtrar por faculty_id, classroom_id y status 'Aprobado'
    if (parseInt(res.faculty_id) !== parseInt(form.faculty_id)) return;
    if (parseInt(res.classroom_id) !== parseInt(form.classroom_id)) return;
    if (res.status !== 'Aprobado') return;

    // Parsear irregular_dates si existe
    let irreg = res.irregular_dates;
    if (typeof irreg === 'string') {
      try {
        irreg = JSON.parse(irreg);
      } catch (e) {
        console.error('Error parsing irregular_dates:', irreg);
        return;
      }
    }
    if (Array.isArray(irreg) && irreg.length > 0) {
      // Verificar coincidencia de fecha y solapamiento de horas
      irreg.forEach(d => {
        if (d.date !== newDateObj.date) return;
        
        const dStart = new Date(`${d.date}T${d.startTime}:00`);
        const dEnd = new Date(`${d.date}T${d.endTime}:00`);

        // Solapamiento: si newStart < dEnd y newEnd > dStart
        if (newStart < dEnd && newEnd > dStart) {
          foundConflicts.push({
            event_title: res.event_title,
            date: d.date,
            startTime: d.startTime,
            endTime: d.endTime,
            newDate: newDateObj.date,
            newStartTime: newDateObj.startTime,
            newEndTime: newDateObj.endTime,
            isInternal: false
          });
        }
      });
    }

    // También verificar contra la fecha principal de otras reservaciones
    if (res.start_datetime && res.end_datetime) {
      const resDate = res.start_datetime.substring(0, 10);
      if (resDate === newDateObj.date) {
        const resStartTime = res.start_datetime.substring(11, 16);
        const resEndTime = res.end_datetime.substring(11, 16);
        const dStart = new Date(`${resDate}T${resStartTime}:00`);
        const dEnd = new Date(`${resDate}T${resEndTime}:00`);

        // Solapamiento: si newStart < dEnd y newEnd > dStart
        if (newStart < dEnd && newEnd > dStart) {
          foundConflicts.push({
            event_title: res.event_title,
            date: resDate,
            startTime: resStartTime,
            endTime: resEndTime,
            newDate: newDateObj.date,
            newStartTime: newDateObj.startTime,
            newEndTime: newDateObj.endTime,
            isInternal: false
          });
        }
      }
    }
  });

  return foundConflicts;
};

// Agregar fecha irregular - SOLO MODIFICA EL ARRAY LOCAL
const addIrregularDate = () => {
  if (newIrregularDate.value.date && newIrregularDate.value.startTime && newIrregularDate.value.endTime) {
    const newDateObj = {
      date: newIrregularDate.value.date,
      startTime: newIrregularDate.value.startTime,
      endTime: newIrregularDate.value.endTime
    };

    // Actualizar displayText manualmente
    updateDisplayText(newDateObj);

    // Verificar conflictos internos (dentro de la misma reservación)
    const internalConflicts = checkInternalConflicts(newDateObj);
    if (internalConflicts.length > 0) {
      internalConflicts.forEach(conflict => {
        addConflict(conflict);
      });
    }

    // Verificar conflictos externos (con otras reservaciones)
    const externalConflicts = checkIrregularDateConflicts(newDateObj);
    if (externalConflicts.length > 0) {
      externalConflicts.forEach(conflict => {
        addConflict(conflict);
      });
    }

    // SOLO agregar al array local - NO actualizar el formulario automáticamente
    irregularDates.value.push(newDateObj);
    newIrregularDate.value = { date: '', startTime: '', endTime: '' };
    
    console.log('Fecha agregada al array local (no guardada aún):', newDateObj);
  }
};

// Eliminar fecha irregular - SOLO MODIFICA EL ARRAY LOCAL
const removeIrregularDate = (index) => {
  const removedDate = irregularDates.value[index];
  irregularDates.value.splice(index, 1);
  
  // Remover conflictos relacionados con esta fecha
  conflicts.value = conflicts.value.filter(conflict => 
    !(conflict.newDate === removedDate.date && 
      conflict.newStartTime === removedDate.startTime && 
      conflict.newEndTime === removedDate.endTime)
  );
  
  // Ocultar el área de conflictos si no hay más conflictos
  if (conflicts.value.length === 0) {
    showConflicts.value = false;
  }
  
  console.log('Fecha eliminada del array local (no guardada aún). Array actual:', JSON.stringify(irregularDates.value, null, 2));
};

// Preparar datos para envío - AQUÍ SE SINCRONIZA CON EL FORMULARIO
const prepareSubmit = () => {
  // Extraer la fecha principal del array
  let mainDateObj = irregularDates.value.find(d => d.isMain);
  
  if (!mainDateObj && irregularDates.value.length > 0) {
    mainDateObj = irregularDates.value[0];
    mainDateObj.isMain = true;
  }
  
  if (mainDateObj) {
    form.start_datetime = `${mainDateObj.date}T${mainDateObj.startTime}:00`;
    form.end_datetime = `${mainDateObj.date}T${mainDateObj.endTime}:00`;
  } else {
    form.start_datetime = null;
    form.end_datetime = null;
  }

  // CORRECCIÓN: Guardar TODAS las fechas irregulares, no solo las adicionales
  // Limpiar los objetos antes de guardar (eliminar propiedades UI como displayText e isMain)
  const cleanedDates = irregularDates.value.map(d => ({
    date: d.date,
    startTime: d.startTime,
    endTime: d.endTime
  }));
  
  form.irregular_dates = cleanedDates.length > 0 ? JSON.stringify(cleanedDates) : null;
  
  console.log('Prepared irregular_dates for submission:', form.irregular_dates);
  
  // Limpiar campos vacíos
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
  
  if (isSubmitting.value) return;
  
  isSubmitting.value = true;
  
  // Preparar datos justo antes del envío
  prepareSubmit();
  console.log(' Datos a enviar:');
  console.log('- irregular_dates en form:', form.irregular_dates);
  console.log('- Cantidad de fechas:', irregularDates.value.length);
  console.log('- Fechas completas:', JSON.stringify(irregularDates.value, null, 2));
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
      console.log('Form submitted successfully');
      isSubmitting.value = false;
      emit('close');
    },
    onError: (errors) => {
      console.error('Form submission errors:', errors);
      isSubmitting.value = false;
    },
    onFinish: () => {
      isSubmitting.value = false;
    }
  });
};

// Cerrar modal y cancelar operación
const close = () => {
  if (isSubmitting.value) {
    form.cancel();
    console.log('Operación de guardado cancelada');
  }
  
  isSubmitting.value = false;
  // Resetear todo a los valores originales (incluyendo irregularDates)
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

            <!-- Área de conflictos -->
            <div v-if="showConflicts" class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
              <div class="flex items-start">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3 flex-1">
                  <h3 class="text-sm font-medium text-red-800 dark:text-red-400">
                    Conflictos de Horario Detectados
                  </h3>
                  <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                    <p class="mb-2">Las siguientes fechas y horarios coinciden con reservaciones ya aprobadas:</p>
                    <ul class="space-y-2">
                      <li v-for="(conflict, index) in conflicts" :key="index" class="bg-white dark:bg-gray-800 p-3 rounded border-l-4 border-red-400">
                        <div class="flex justify-between items-start">
                          <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">
                              {{ conflict.event_title }}
                            </p>
                            <p class="text-gray-600 dark:text-gray-400">
                              <strong>Fecha:</strong> {{ conflict.date }}
                            </p>
                            <p class="text-gray-600 dark:text-gray-400">
                              <strong>Horario ocupado:</strong> {{ conflict.startTime }} - {{ conflict.endTime }}
                            </p>
                            <p class="text-red-600 dark:text-red-400">
                              <strong>Nueva fecha solicitada:</strong> {{ conflict.newStartTime }} - {{ conflict.newEndTime }}
                            </p>
                          </div>
                          <button 
                            type="button" 
                            @click="conflicts.splice(index, 1); if (conflicts.length === 0) showConflicts = false"
                            class="ml-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            title="Descartar este conflicto"
                          >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                          </button>
                        </div>
                      </li>
                    </ul>
                  </div>
                  <div class="mt-3">
                    <button 
                      type="button" 
                      @click="clearConflicts" 
                      class="text-sm bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 px-3 py-1 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                    >
                      Limpiar todos los conflictos
                    </button>
                  </div>
                </div>
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
                            <span v-if="date.isMain" class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded ml-2">Principal</span>
                          </td>
                          <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                            <select v-model="date.startTime" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                              <option v-for="time in timeOptions" :key="time" :value="time">{{ time }}</option>
                            </select>
                          </td>
                          <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                            <select v-model="date.endTime" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
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