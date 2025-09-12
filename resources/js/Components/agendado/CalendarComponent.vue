<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  initialDate: String,
  initialStart: String,
  initialEnd: String,
  classroomId: {
    type: [Number, String],
    default: null
  },
  persistedEvents: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update-form', 'events-updated']);

const calendarEl = ref(null);
let calendar = null;
const currentWeek = ref(0);
const selectedDay = ref(null);
const isModalOpen = ref(false);
const tempStartTime = ref('');
const tempEndTime = ref('');
const modalInitialTime = ref(null);
const isRecurring = ref(false);
const recurringDays = ref([]);
const recurringFrequency = ref('');
const repetitionCount = ref(null);
const create_events = ref([]); // Array local para eventos del usuario
const existing_reservations = ref([]); // Array para reservaciones existentes en BD
const isLoadingReservations = ref(false);
const conflictMessage = ref('');

const timeOptions = ref([
  '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
  '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30',
  '17:00', '17:30', '18:00', '18:30', '19:00', '19:30', '20:00'
]);

const getCurrentWeekDates = computed(() => {
  const today = new Date();
  const firstDayOfWeek = new Date(today);
  firstDayOfWeek.setDate(today.getDate() - today.getDay() + 1);
  firstDayOfWeek.setDate(firstDayOfWeek.getDate() + currentWeek.value * 7);
  const weekDates = [];
  for (let i = 0; i < 5; i++) {
    const dayDate = new Date(firstDayOfWeek.getFullYear(), firstDayOfWeek.getMonth(), firstDayOfWeek.getDate() + i);
    weekDates.push(dayDate);
  }
  return weekDates;
});

// Función para cargar eventos desde las props persistidas
const loadPersistedEvents = () => {
  if (props.persistedEvents && props.persistedEvents.length > 0) {
    create_events.value = [...props.persistedEvents];
    console.log('Eventos cargados desde persistedEvents:', create_events.value);
    updateCalendarEvents();
  }
};

// Watch para detectar cambios en los eventos persistidos desde el padre
watch(() => props.persistedEvents, (newEvents) => {
  if (newEvents && newEvents.length >= 0) {
    create_events.value = [...newEvents];
    console.log('Eventos actualizados desde el padre:', create_events.value);
    updateCalendarEvents();
  }
}, { deep: true });

// Función para emitir eventos actualizados al padre
const emitEventsUpdate = () => {
  emit('events-updated', [...create_events.value]);
};

// Función para cargar reservaciones existentes
const loadExistingReservations = async () => {
  if (!props.classroomId) {
    existing_reservations.value = [];
    return;
  }

  isLoadingReservations.value = true;
  try {
    const response = await axios.get('/existing-reservations', {
      params: {
        classroom_id: props.classroomId
      }
    });
    existing_reservations.value = response.data.existing_reservations || [];
    updateCalendarEvents();
  } catch (error) {
    console.error('Error loading existing reservations:', error);
    existing_reservations.value = [];
  } finally {
    isLoadingReservations.value = false;
  }
};

// Función para verificar conflictos
const checkForConflicts = async (dates) => {
  if (!props.classroomId || !dates.length) return { has_conflicts: false, conflicts: [] };

  try {
    const response = await axios.post('/check-reservation-conflicts', {
      classroom_id: props.classroomId,
      dates: dates
    });
    return response.data;
  } catch (error) {
    console.error('Error checking conflicts:', error);
    return { has_conflicts: false, conflicts: [] };
  }
};

// Watch para cargar reservaciones cuando cambie el classroom_id
watch(() => props.classroomId, (newClassroomId) => {
  if (newClassroomId) {
    loadExistingReservations();
  } else {
    existing_reservations.value = [];
    updateCalendarEvents();
  }
}, { immediate: true });

const previousWeek = () => {
  currentWeek.value--;
  if (calendar) {
    calendar.gotoDate(getCurrentWeekDates.value[0]);
    updateCalendarEvents();
  }
  selectedDay.value = null;
};

const nextWeek = () => {
  currentWeek.value++;
  if (calendar) {
    calendar.gotoDate(getCurrentWeekDates.value[0]);
    updateCalendarEvents();
  }
  selectedDay.value = null;
};

const selectDay = (dayIndex) => {
  selectedDay.value = getCurrentWeekDates.value[dayIndex];
  if (calendar) {
    calendar.gotoDate(selectedDay.value);
  }
};


const openModal = (day, initialTime = null, eventIndex = null) => {
  selectedDay.value = day;
  modalInitialTime.value = initialTime;
  editingEventIndex.value = eventIndex; // Asignar el índice de edición
  
  // Resetear valores por defecto
  tempStartTime.value = initialTime || '';
  tempEndTime.value = '';
  isRecurring.value = false;
  recurringDays.value = [];
  recurringFrequency.value = '';
  repetitionCount.value = null;
  conflictMessage.value = '';

  // Si estamos editando un evento existente
  if (eventIndex !== null && create_events.value[eventIndex]) {
    const eventKey = `event_${eventIndex + 1}`;
    const event = create_events.value[eventIndex][eventKey];
    
    console.log('Editando evento en índice:', eventIndex, 'Datos del evento:', event);
    
    // Extraer hora de inicio y fin correctamente
    const startDateTime = event.start_datetime; // formato: "2024-01-15 09:00:00"
    const endDateTime = event.end_datetime;     // formato: "2024-01-15 11:00:00"
    
    // Extraer solo la parte de la hora (HH:MM)
    tempStartTime.value = startDateTime.split(' ')[1]?.substring(0, 5) || '';
    tempEndTime.value = endDateTime.split(' ')[1]?.substring(0, 5) || '';
    
    // Configurar datos de recurrencia
    if (event.recurring_days !== null && event.recurring_days !== undefined) {
      isRecurring.value = true;
      
      try {
        // Parsear los días recurrentes
        recurringDays.value = JSON.parse(event.recurring_days) || [];
      } catch (e) {
        console.error('Error parsing recurring_days:', e);
        recurringDays.value = [];
      }
      
      recurringFrequency.value = event.recurring_frequency || '';
      repetitionCount.value = event.repeticion || null; // Nota: es 'repeticion', no 'repetion'
    } else {
      isRecurring.value = false;
      recurringDays.value = [];
      recurringFrequency.value = '';
      repetitionCount.value = null;
    }

    console.log('Editando evento - datos cargados:', {
      startTime: tempStartTime.value,
      endTime: tempEndTime.value,
      isRecurring: isRecurring.value,
      recurringDays: recurringDays.value,
      frequency: recurringFrequency.value,
      repetitions: repetitionCount.value,
      editingIndex: editingEventIndex.value
    });
  } else {
    console.log('Creando nuevo evento');
  }

  isModalOpen.value = true;
  
  // Asegurar que las opciones de recurrencia se muestren si es necesario
  setTimeout(() => {
    const el = document.querySelector('.recurring-options');
    if (el && isRecurring.value) {
      el.classList.add('show-recurring');
    }
  }, 0);
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedDay.value = null;
  modalInitialTime.value = null;
  editingEventIndex.value = null; // Resetear el índice de edición
  tempStartTime.value = '';
  tempEndTime.value = '';
  isRecurring.value = false;
  recurringDays.value = [];
  recurringFrequency.value = '';
  repetitionCount.value = null;
  conflictMessage.value = '';
};

const handleRecurringChange = (event) => {
  isRecurring.value = event.target.checked;
  setTimeout(() => {
    const el = document.querySelector('.recurring-options');
    if (el) {
      el.classList.toggle('show-recurring', isRecurring.value);
    }
  }, 0);
};

const nthWeekdayOfMonth = (weekday, n, date) => {
  let d = new Date(date.getFullYear(), date.getMonth(), 1);
  let add = (weekday - d.getDay() + 7) % 7 + (n - 1) * 7;
  d.setDate(1 + add);
  const originalMonth = d.getMonth();
  if (d.getMonth() !== originalMonth) {
    d = new Date(d.getFullYear(), originalMonth + 1, 0);
    while (d.getDay() !== weekday) {
      d.setDate(d.getDate() - 1);
    }
  }
  return d;
};

// Variable para almacenar conflictos detectados
const detectedConflicts = ref([]);

// Función saveTime modificada para CalendarComponent.vue
// Variable para rastrear si estamos editando un evento existente
const editingEventIndex = ref(null);

const saveTime = async () => {
  const dateStr = selectedDay.value.toISOString().split('T')[0];
  const baseDate = new Date(dateStr + 'T00:00:00');

  const start_datetime = `${dateStr} ${tempStartTime.value}:00`;
  const end_datetime = `${dateStr} ${tempEndTime.value}:00`;
  
  // Preparar datos para verificación de conflictos
  let datesToCheck = [{
    date: dateStr,
    start_time: tempStartTime.value,
    end_time: tempEndTime.value
  }];

  const recurringData = {
    is_recurring: isRecurring.value,
    recurring_days: isRecurring.value && recurringDays.value.length > 0 ? JSON.stringify(
      // Asegurar que sea un array válido
      Array.isArray(recurringDays.value) ? 
        recurringDays.value.sort((a, b) => {
          const order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
          return order.indexOf(a) - order.indexOf(b);
        }) : 
        Object.values(recurringDays.value).sort((a, b) => {
          const order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
          return order.indexOf(a) - order.indexOf(b);
        })
    ) : null,
    recurring_frequency: isRecurring.value ? recurringFrequency.value : null,
    repeticion: isRecurring.value ? repetitionCount.value : null,
  };

  // Calcular irregular_dates para el evento actual (único o repetible)
  let current_irregular_dates = [];
  const startTime = tempStartTime.value;
  const endTime = tempEndTime.value;

  if (isRecurring.value && recurringDays.value.length > 0 && repetitionCount.value) {
    // Lógica para eventos repetibles (mantener la existente)
    const daysMap = { 'Monday': 1, 'Tuesday': 2, 'Wednesday': 3, 'Thursday': 4, 'Friday': 5 };

if (recurringFrequency.value === 'weekly') {
  // repetitionCount.value = 1 significa: semana actual + 1 repetición = 2 semanas total
  const totalWeeks = repetitionCount.value + 1;
  
  for (let weekCount = 0; weekCount < totalWeeks; weekCount++) {
    recurringDays.value.forEach(day => {
      // Crear fecha para esta semana específica
      let weekStart = new Date(baseDate);
      
      // Calcular el lunes de la semana actual
      const baseDayNum = weekStart.getDay(); // 0=Sunday, 1=Monday, etc.
      const daysFromMonday = (baseDayNum === 0) ? 6 : baseDayNum - 1; // Domingo = 6 días desde lunes
      weekStart.setDate(weekStart.getDate() - daysFromMonday);
      
      // Ahora weekStart es el lunes de la semana base
      // Agregar las semanas correspondientes
      weekStart.setDate(weekStart.getDate() + (weekCount * 7));
      
      // Agregar días para llegar al día objetivo
      const targetDayNum = daysMap[day]; // 1=Monday, 2=Tuesday, etc.
      const targetDate = new Date(weekStart);
      targetDate.setDate(weekStart.getDate() + (targetDayNum - 1)); // -1 porque Monday=1 pero es 0 días desde Monday
      
      // Solo incluir fechas que sean >= baseDate para la primera semana
      if (weekCount === 0 && targetDate < baseDate) {
        return; // Saltar este día si ya pasó en la semana actual
      }

      const dateString = targetDate.toISOString().split('T')[0];
      const displayText = `${dateString} ${startTime} - ${endTime}`;
      
      current_irregular_dates.push({
        date: dateString,
        startTime: startTime,
        endTime: endTime,
        displayText: displayText
      });

      datesToCheck.push({
        date: dateString,
        start_time: startTime,
        end_time: endTime
      });
    });
  }
}else if (recurringFrequency.value === 'monthly') {
  // Lógica mensual corregida
  const baseDayNum = baseDate.getDay();
  const dayDiff = (baseDayNum - 1 + 7) % 7;
  let mondayDate = new Date(baseDate);
  mondayDate.setDate(baseDate.getDate() - dayDiff);
  const nth = Math.ceil(mondayDate.getDate() / 7);

  let currentMonth = baseDate.getMonth();
  let currentYear = baseDate.getFullYear();

  for (let i = 0; i <= repetitionCount.value; i++) {
    let monthDate = new Date(currentYear, currentMonth, 1);
    let nthMonday = nthWeekdayOfMonth(1, nth, monthDate);

    recurringDays.value.forEach(day => {
      const weekdayNum = daysMap[day];
      let date = new Date(nthMonday);
      date.setDate(date.getDate() + (weekdayNum - 1));
      
      const originalMonth = monthDate.getMonth();
      if (date.getMonth() !== originalMonth) {
        date = new Date(currentYear, originalMonth + 1, 0);
        while (date.getDay() !== weekdayNum) {
          date.setDate(date.getDate() - 1);
        }
      }

      // CORRECCIÓN: Solo agregar la fecha si es >= a la fecha base seleccionada
      // o si estamos en un mes posterior al mes base
      if (i === 0) {
        // Para el primer mes (mes actual), solo agregar fechas >= fecha base
        if (date >= baseDate) {
          const dateString = date.toISOString().split('T')[0];
          const displayText = `${dateString} ${startTime} - ${endTime}`;
          current_irregular_dates.push({
            date: dateString,
            startTime: startTime,
            endTime: endTime,
            displayText: displayText
          });

          datesToCheck.push({
            date: dateString,
            start_time: startTime,
            end_time: endTime
          });
        }
      } else {
        // Para meses posteriores, agregar todas las fechas normalmente
        const dateString = date.toISOString().split('T')[0];
        const displayText = `${dateString} ${startTime} - ${endTime}`;
        current_irregular_dates.push({
          date: dateString,
          startTime: startTime,
          endTime: endTime,
          displayText: displayText
        });

        datesToCheck.push({
          date: dateString,
          start_time: startTime,
          end_time: endTime
        });
      }
    });

    currentMonth += 1;
    if (currentMonth > 11) {
      currentMonth = 0;
      currentYear += 1;
    }
  }
}
  } else {
    // Para eventos únicos, crear un solo elemento
    const displayText = `${dateStr} ${startTime} - ${endTime}`;
    current_irregular_dates.push({
      date: dateStr,
      startTime: startTime,
      endTime: endTime,
      displayText: displayText
    });
  }

  // Verificar conflictos
  const conflictCheck = await checkForConflicts(datesToCheck);
  if (conflictCheck.has_conflicts) {
    const conflictsList = conflictCheck.conflicts.map(conflict => 
      `${conflict.date} ${conflict.requested_start}-${conflict.requested_end} (Conflicto con: ${conflict.existing_reservation.title})`
    ).join('\n');
    
    conflictMessage.value = `⚠️ ADVERTENCIA - Se detectaron conflictos de horario:\n${conflictsList}\n\nAun así puedes guardar tu reservación, pero será enviada para revisión manual, además tiene la probabilidad alta de ser rechazada.`;
    detectedConflicts.value = conflictCheck.conflicts;
  } else {
    conflictMessage.value = '';
    detectedConflicts.value = [];
  }

  // Crear el objeto del evento
  const newEvent = {
    start_datetime: start_datetime,
    end_datetime: end_datetime,
    recurring_days: recurringData.recurring_days,
    recurring_frequency: recurringData.recurring_frequency,
    repeticion: recurringData.repeticion,
    irregular_dates: JSON.stringify(current_irregular_dates), // Solo las fechas del evento actual
    has_conflicts: conflictCheck.has_conflicts
  };

  // LÓGICA NUEVA: Manejar eventos repetidos
  if (editingEventIndex.value !== null) {
    // EDITANDO evento existente - reemplazar el evento en el índice específico
    const eventObj = { [`event_${editingEventIndex.value + 1}`]: newEvent };
    create_events.value.splice(editingEventIndex.value, 1, eventObj);
    console.log(`Evento ${editingEventIndex.value + 1} modificado exitosamente`);
  } else {
    // CREANDO nuevo evento
    if (isRecurring.value) {
      // Si el nuevo evento es repetido, buscar si ya existe un evento repetido
      const existingRecurringIndex = create_events.value.findIndex((eventObj, index) => {
        const eventKey = `event_${index + 1}`;
        const event = eventObj[eventKey];
        return event.recurring_days !== null && event.recurring_days !== undefined;
      });

      if (existingRecurringIndex !== -1) {
        // YA EXISTE un evento repetido - reemplazarlo
        const eventObj = { [`event_${existingRecurringIndex + 1}`]: newEvent };
        create_events.value.splice(existingRecurringIndex, 1, eventObj);
        console.log(`Evento repetido existente ${existingRecurringIndex + 1} modificado exitosamente`);
      } else {
        // NO EXISTE evento repetido - crear uno nuevo
        const eventIndex = create_events.value.length;
        const eventObj = { [`event_${eventIndex + 1}`]: newEvent };
        create_events.value = [...create_events.value, eventObj];
        console.log(`Nuevo evento repetido ${eventIndex + 1} creado exitosamente`);
      }
    } else {
      // Evento único - crear normalmente
      const eventIndex = create_events.value.length;
      const eventObj = { [`event_${eventIndex + 1}`]: newEvent };
      create_events.value = [...create_events.value, eventObj];
      console.log(`Nuevo evento único ${eventIndex + 1} creado exitosamente`);
    }
  }

  // NUEVA LÓGICA: Consolidar todos los irregular_dates de todos los eventos
  let consolidated_irregular_dates = [];
  create_events.value.forEach((eventObj, index) => {
    const eventKey = `event_${index + 1}`;
    const event = eventObj[eventKey];
    if (event.irregular_dates) {
      const eventDates = JSON.parse(event.irregular_dates);
      consolidated_irregular_dates = consolidated_irregular_dates.concat(eventDates);
    }
  });

  // NUEVA LÓGICA: Determinar datos de repetición basados en el último evento repetido
  let global_is_recurring = false;
  let global_recurring_days = null;
  let global_recurring_frequency = null;
  let global_repeticion = null;

  // Buscar el último evento repetido en create_events    Aqui se modifica el array
for (let i = create_events.value.length - 1; i >= 0; i--) {
  const eventKey = `event_${i + 1}`;
  const event = create_events.value[i][eventKey];
  if (event.recurring_days !== null) {
    global_is_recurring = true;
    // CORRECCIÓN: Asegurar que recurring_days siempre sea un string JSON
    global_recurring_days = event.recurring_days; // Ya es string porque así se guardó
    global_recurring_frequency = event.recurring_frequency;
    global_repeticion = event.repeticion;
    break;
  }
}

  // Si no hay eventos repetidos, verificar si hay al menos un evento
  if (!global_is_recurring && create_events.value.length > 0) {
    global_is_recurring = false;
  }

  // Emitir eventos actualizados al padre
  emitEventsUpdate();

  // Emitir los datos consolidados a través de update-form
  emit('update-form', dateStr, tempStartTime.value, tempEndTime.value, {
    is_recurring: global_is_recurring,
    recurring_days: global_recurring_days,
    recurring_frequency: global_recurring_frequency,
    repeticion: global_repeticion,
    irregular_dates: JSON.stringify(consolidated_irregular_dates) // Array consolidado
  });

  updateCalendarEvents();
  closeModal();
};

const editEvent = (index) => {
  const event = create_events.value[index][`event_${index + 1}`];
  const startDateParts = event.start_datetime.split(' ')[0].split('-');
  const day = new Date(startDateParts[0], startDateParts[1] - 1, startDateParts[2]);
  openModal(day, event.start_datetime.split(' ')[1], index);
};

const deleteEvent = (index) => {
  // Eliminar el evento del array
  create_events.value.splice(index, 1);
  
  // Reindexar los eventos restantes para mantener la secuencia event_1, event_2, etc.
  create_events.value = create_events.value.map((eventObj, newIndex) => {
    const oldEventKey = Object.keys(eventObj)[0];
    const eventData = eventObj[oldEventKey];
    return { [`event_${newIndex + 1}`]: eventData };
  });
  
  // CONSOLIDAR nuevamente todos los irregular_dates de todos los eventos restantes
  let consolidated_irregular_dates = [];
  create_events.value.forEach((eventObj, eventIndex) => {
    const eventKey = `event_${eventIndex + 1}`;
    const event = eventObj[eventKey];
    if (event.irregular_dates) {
      const eventDates = JSON.parse(event.irregular_dates);
      consolidated_irregular_dates = consolidated_irregular_dates.concat(eventDates);
    }
  });

  // Determinar datos de repetición basados en el último evento repetido restante
  let global_is_recurring = false;
  let global_recurring_days = null;
  let global_recurring_frequency = null;
  let global_repeticion = null;

  // Buscar el último evento repetido en create_events restantes
for (let i = create_events.value.length - 1; i >= 0; i--) {
  const eventKey = `event_${i + 1}`;
  const event = create_events.value[i][eventKey];
  if (event.recurring_days !== null) {
    global_is_recurring = true;
    // CORRECCIÓN: Asegurar que recurring_days siempre sea un string JSON
    global_recurring_days = event.recurring_days; // Ya es string porque así se guardó
    global_recurring_frequency = event.recurring_frequency;
    global_repeticion = event.repeticion;
    break;
  }
}

  // Si no hay eventos repetidos, verificar si hay al menos un evento
  if (!global_is_recurring && create_events.value.length > 0) {
    global_is_recurring = false;
  }

  // Emitir eventos actualizados al padre
  emitEventsUpdate();

  // Emitir los datos consolidados actualizados a través de update-form
  if (create_events.value.length > 0) {
    const lastEvent = create_events.value[create_events.value.length - 1];
    const lastEventKey = Object.keys(lastEvent)[0];
    const lastEventData = lastEvent[lastEventKey];
    const lastEventDate = lastEventData.start_datetime.split(' ')[0];
    const lastEventStartTime = lastEventData.start_datetime.split(' ')[1].substring(0, 5);
    const lastEventEndTime = lastEventData.end_datetime.split(' ')[1].substring(0, 5);

    emit('update-form', lastEventDate, lastEventStartTime, lastEventEndTime, {
      is_recurring: global_is_recurring,
      recurring_days: global_recurring_days,
      recurring_frequency: global_recurring_frequency,
      repeticion: global_repeticion,
      irregular_dates: JSON.stringify(consolidated_irregular_dates) // Array consolidado actualizado
    });
  } else {
    // Si no quedan eventos, enviar datos vacíos
    emit('update-form', '', '', '', {
      is_recurring: false,
      recurring_days: null,
      recurring_frequency: null,
      repeticion: null,
      irregular_dates: JSON.stringify([]) // Array vacío
    });
  }

  updateCalendarEvents();
};

const handleSelect = (info) => {
  const selectedDate = new Date(info.start);
  const dayName = selectedDate.toLocaleDateString('es-MX', { weekday: 'long' }).toLowerCase();
  if (['lunes', 'martes', 'miércoles', 'jueves', 'viernes'].includes(dayName)) {
    const dayDate = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());
    const initialTime = selectedDate.toTimeString().slice(0, 5);
    openModal(dayDate, initialTime);
  }
};

const updateCalendarEvents = () => {
  if (calendar) {
    const events = [];
    
    // Eventos del usuario (nuevos)
    create_events.value.forEach((eventObj, index) => {
      const eventKey = `event_${index + 1}`;
      const event = eventObj[eventKey];
      const start = new Date(event.start_datetime);
      const end = new Date(event.end_datetime);

      // Color diferente si tiene conflictos
      const eventColor = event.has_conflicts ? '#f59e0b' : '#10b981'; // Amarillo si tiene conflictos, verde si no
      const eventTitle = event.has_conflicts ? 'Mi Reserva (⚠️ Conflicto)' : 'Mi Reserva';

      events.push({ 
        title: eventTitle, 
        start, 
        end, 
        color: eventColor,
        classNames: ['user-event'],
        borderColor: event.has_conflicts ? '#dc2626' : eventColor
      });

      // Si es un evento repetible, añadir eventos para cada día especificado en irregular_dates
      if (event.irregular_dates) {
        const irregularDates = JSON.parse(event.irregular_dates);
        irregularDates.forEach(irregular => {
          const irregularStart = new Date(`${irregular.date} ${irregular.startTime}:00`);
          const irregularEnd = new Date(`${irregular.date} ${irregular.endTime}:00`);
          if (isWithinCurrentWeek(irregularStart)) {
            events.push({ 
              title: eventTitle, 
              start: irregularStart, 
              end: irregularEnd, 
              color: eventColor,
              classNames: ['user-event'],
              borderColor: event.has_conflicts ? '#dc2626' : eventColor
            });
          }
        });
      }
    });

    // Reservaciones existentes (de otros usuarios)
    existing_reservations.value.forEach(reservation => {
      const start = new Date(reservation.start_datetime);
      const end = new Date(reservation.end_datetime);
      
      if (isWithinCurrentWeek(start)) {
        events.push({
          title: `${reservation.title} - ${reservation.organizer}`,
          start,
          end,
          color: '#ef4444',
          classNames: ['existing-event'],
          extendedProps: {
            organizer: reservation.organizer,
            reservationId: reservation.id
          }
        });
      }
    });

    calendar.removeAllEvents();
    calendar.addEventSource(events);
    calendar.render();
  }
};

const isWithinCurrentWeek = (date) => {
  const weekStart = getCurrentWeekDates.value[0];
  const weekEnd = new Date(getCurrentWeekDates.value[4]);
  weekEnd.setHours(23, 59, 59, 999);
  return date >= weekStart && date <= weekEnd;
};

// Watch para detectar cambios en las reservaciones existentes
watch(existing_reservations, () => {
  updateCalendarEvents();
}, { deep: true });

watch(isRecurring, (newVal) => {
  const el = document.querySelector('.recurring-options');
  if (el) {
    el.classList.toggle('show-recurring', newVal);
  }
});

onMounted(() => {
  if (!window.FullCalendar) {
    console.error('FullCalendar is not loaded. Ensure CDN scripts are included.');
    return;
  }

  nextTick(() => {
    if (calendarEl.value) {
      calendar = new FullCalendar.Calendar(calendarEl.value, {
        initialView: 'timeGridWeek',
        headerToolbar: {
          left: 'prev',
          center: 'title',
          right: 'next'
        },
        customButtons: {
          prev: { text: 'Semana Anterior', click: previousWeek },
          next: { text: 'Próxima Semana', click: nextWeek }
        },
        slotMinTime: '07:00:00',
        slotMaxTime: '20:30:00',
        slotDuration: '00:30:00',
        slotLabelInterval: '00:30:00',
        allDaySlot: false,
        hiddenDays: [0, 6],
        selectable: true,
        select: handleSelect,
        height: 'auto',
        initialDate: props.initialDate || new Date().toISOString().split('T')[0],
        events: [],
        eventClick: function(info) {
          // Mostrar información del evento cuando se hace clic
          if (info.event.classNames.includes('existing-event')) {
            alert(`Reserva existente:\n${info.event.title}\nOrganizador: ${info.event.extendedProps.organizer}`);
          }
        }
      });
      calendar.render();
      
      // Cargar reservaciones si ya tenemos classroom_id
      if (props.classroomId) {
        loadExistingReservations();
      }
      
      // Cargar eventos persistidos al montar el componente
      loadPersistedEvents();
    } else {
      console.error('calendarEl is null. Check the DOM rendering.');
    }
  });
});

onUnmounted(() => {
  if (calendar) {
    calendar.destroy();
    calendar = null;
  }
});
</script>

<style scoped>
.show-recurring {
  display: block !important;
  min-height: 100px !important;
}

.reservation-item {
  display: flex;
  justify-content: space-between;
  padding: 10px;
  margin: 5px 0;
  background-color: #f9f9f9;
  border-radius: 5px;
  border-left: 4px solid #10b981;
}

.dark .reservation-item {
  background-color: #4a4a4a;
  color: #e5e7eb;
}

.reservation-item span {
  font-size: 0.875rem;
  color: #374151;
}

.dark .reservation-item span {
  color: #e5e7eb;
}

.reservation-item .text-sm {
  font-size: 0.875rem;
  color: #6b7280;
}

.dark .reservation-item .text-sm {
  color: #d1d5db;
}

.conflict-alert {
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 10px;
  border-radius: 5px;
  margin-bottom: 15px;
}

.dark .conflict-alert {
  background-color: #7f1d1d;
  border-color: #dc2626;
  color: #fca5a5;
}

.loading-spinner {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #3498db;
  border-radius: 50%;
  animation: spin 2s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>

<template>
  <div class="flex space-x-6">
    <!-- Columna izquierda: Selección de días -->
    <div class="w-1/4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200 mb-4">Seleccionar Día</h3>
      
      <!-- Indicador de carga -->
      <div v-if="isLoadingReservations" class="flex items-center justify-center p-4">
        <div class="loading-spinner"></div>
        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Cargando reservaciones...</span>
      </div>
      
      <!-- Mensaje si no hay aula seleccionada -->
      <div v-else-if="!classroomId" class="p-4 text-center text-gray-500 dark:text-gray-400 text-sm">
        Seleccione un aula en el paso anterior para ver disponibilidad
      </div>
      
      <!-- Botones de días -->
      <div v-else class="space-y-2">
        <button
          v-for="(day, index) in getCurrentWeekDates"
          :key="day"
          @click="openModal(day)"
          class="w-full py-2 px-4 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-700 dark:hover:bg-indigo-800 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!classroomId"
        >
          {{ day.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'short' }) }}
        </button>
      </div>

      <!-- Leyenda y alertas de conflictos -->
      <div v-if="classroomId" class="mt-4">
        <!-- Leyenda -->
        <div class="text-xs mb-3">
          <div class="flex items-center mb-1">
            <div class="w-3 h-3 bg-green-500 rounded mr-2"></div>
            <span class="text-gray-600 dark:text-gray-400">Mis reservas</span>
          </div>
          <div class="flex items-center">
            <div class="w-3 h-3 bg-red-500 rounded mr-2"></div>
            <span class="text-gray-600 dark:text-gray-400">Reservas existentes</span>
          </div>
        </div>
        
        <!-- Alerta de conflictos globales -->
        <div v-if="detectedConflicts.length > 0" class="conflict-alert mb-4">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              ⚠️
            </div>
            <div class="ml-2">
              <h4 class="font-semibold mb-2">Conflictos de Horario Detectados</h4>
              <p class="text-sm mb-2">
                Las siguientes reservaciones tienen conflictos con horarios ya ocupados. Tu solicitud será enviada para revisión manual, además tiene la probabilidad alta de ser rechazada:
              </p>
              <ul class="text-xs space-y-1">
                <li v-for="conflict in detectedConflicts" :key="`${conflict.date}-${conflict.requested_start}`" class="pl-2 border-l-2 border-red-300">
                  <strong>{{ conflict.date }}</strong> {{ conflict.requested_start }}-{{ conflict.requested_end }}
                  <br>
                  <span class="text-red-700 dark:text-red-300">Conflicto con: {{ conflict.existing_reservation.title }} ({{ conflict.existing_reservation.organizer }})</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Columna derecha: Calendario -->
    <div class="w-3/4 dark:text-gray-400">
      <div ref="calendarEl"></div>
      
      <!-- Sección de Días a Solicitar -->
      <div class="mt-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200 mb-2">Días a Solicitar</h3>
        <div v-if="create_events.length > 0" class="space-y-2">
          <div v-for="(eventObj, index) in create_events" :key="index" 
               class="reservation-item"
               :class="{ 'border-l-yellow-500 bg-yellow-50 dark:bg-yellow-900': eventObj[`event_${index + 1}`].has_conflicts }">
            <span>
              <div v-if="eventObj[`event_${index + 1}`].has_conflicts" class="flex items-center mb-2">
                <span class="text-yellow-600 dark:text-yellow-400 text-sm font-medium">⚠️ Con Conflictos</span>
              </div>
              <strong>Fecha:</strong> {{ eventObj[`event_${index + 1}`].start_datetime.split(' ')[0] }}<br>
              <strong>Hora de Inicio:</strong> {{ eventObj[`event_${index + 1}`].start_datetime.split(' ')[1] }}<br>
              <strong>Hora de Fin:</strong> {{ eventObj[`event_${index + 1}`].end_datetime.split(' ')[1] }}<br>
              <span v-if="eventObj[`event_${index + 1}`].recurring_days" class="text-sm text-gray-600 dark:text-gray-300">
                <strong>Repeticiones:</strong> {{ eventObj[`event_${index + 1}`].repeticion || 0 }}<br>
                <strong>Tipo:</strong> {{ eventObj[`event_${index + 1}`].recurring_frequency === 'weekly' ? 'Semanal' : eventObj[`event_${index + 1}`].recurring_frequency === 'monthly' ? 'Mensual' : 'No aplica' }}<br>
                <strong>Días:</strong> {{ eventObj[`event_${index + 1}`].recurring_days ? JSON.parse(eventObj[`event_${index + 1}`].recurring_days).map(day => day === 'Monday' ? 'Lunes' : day === 'Tuesday' ? 'Martes' : day === 'Wednesday' ? 'Miércoles' : day === 'Thursday' ? 'Jueves' : day === 'Friday' ? 'Viernes' : day).join(', ') : 'No aplica' }}<br>
                <strong>Fechas:</strong> {{ eventObj[`event_${index + 1}`].irregular_dates ? JSON.parse(eventObj[`event_${index + 1}`].irregular_dates).slice(0, 3).map(d => d.date).join(', ') + (JSON.parse(eventObj[`event_${index + 1}`].irregular_dates).length > 3 ? '...' : '') : 'No aplica' }}
              </span>
            </span>
            <div class="flex flex-col space-y-2">
              <button
                @click="editEvent(index)"
                class="px-2 py-1 bg-yellow-500 text-white rounded-md hover:bg-yellow-600"
              >
                Modificar
              </button>
              <button
                @click="deleteEvent(index)"
                class="px-2 py-1 bg-red-500 text-white rounded-md hover:bg-red-600"
              >
                Eliminar
              </button>
            </div>
          </div>
        </div>
        <p v-else class="text-gray-500 dark:text-gray-400">No hay reservaciones pendientes.</p>
      </div>

      <!-- Información de reservaciones existentes -->
      <div v-if="existing_reservations.length > 0" class="mt-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200 mb-2">Reservaciones Existentes en esta Aula</h3>
        <div class="grid grid-cols-1 gap-2 max-h-40 overflow-y-auto">
          <div v-for="reservation in existing_reservations.slice(0, 5)" :key="reservation.id" 
               class="text-xs p-2 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded">
            <strong>{{ reservation.title }}</strong> - {{ reservation.organizer }}<br>
            {{ reservation.date }} {{ reservation.start_time }} - {{ reservation.end_time }}
          </div>
          <div v-if="existing_reservations.length > 5" class="text-xs text-gray-500 dark:text-gray-400 text-center">
            Y {{ existing_reservations.length - 5 }} reservaciones más...
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para seleccionar horario -->
  <div v-if="isModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96 overflow-auto" :style="{ maxHeight: '90vh' }">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200 mb-4">
        Seleccionar Horario para {{ selectedDay?.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
      </h3>
      
      <!-- Mensaje de conflictos -->
      <div v-if="conflictMessage" class="conflict-alert mb-4">
        <strong>⚠️ Conflicto de Horario:</strong><br>
        {{ conflictMessage }}
      </div>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-900 dark:text-gray-200">Hora de Inicio</label>
          <select
            v-model="tempStartTime"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 sm:text-sm p-2"
          >
            <option value="">Seleccione hora</option>
            <option v-for="time in timeOptions" :key="time" :value="time">{{ time }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-900 dark:text-gray-200">Hora de Fin</label>
          <select
            v-model="tempEndTime"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 sm:text-sm p-2"
          >
            <option value="">Seleccione hora</option>
            <option v-for="time in timeOptions" :key="time" :value="time">{{ time }}</option>
          </select>
        </div>
        <div>
          <label class="inline-flex items-center">
            <input type="checkbox" v-model="isRecurring" @change="handleRecurringChange" class="form-checkbox h-5 w-5 text-indigo-600 dark:text-indigo-400">
            <span class="ml-2 text-sm text-gray-900 dark:text-gray-200">Evento repetible</span>
          </label>
        </div>
        
        <!-- Opciones de recurrencia -->
        <div v-show="isRecurring" class="recurring-options space-y-4 mt-4">
          <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-gray-200">Días de la semana</label>
            <div class="grid grid-cols-2 gap-2">
              <label class="inline-flex items-center">
                <input type="checkbox" v-model="recurringDays" value="Monday" class="form-checkbox h-5 w-5 text-indigo-600 dark:text-indigo-400">
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Lunes</span>
              </label>
              <label class="inline-flex items-center">
                <input type="checkbox" v-model="recurringDays" value="Tuesday" class="form-checkbox h-5 w-5 text-indigo-600 dark:text-indigo-400">
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Martes</span>
              </label>
              <label class="inline-flex items-center">
                <input type="checkbox" v-model="recurringDays" value="Wednesday" class="form-checkbox h-5 w-5 text-indigo-600 dark:text-indigo-400">
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Miércoles</span>
              </label>
              <label class="inline-flex items-center">
                <input type="checkbox" v-model="recurringDays" value="Thursday" class="form-checkbox h-5 w-5 text-indigo-600 dark:text-indigo-400">
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Jueves</span>
              </label>
              <label class="inline-flex items-center">
                <input type="checkbox" v-model="recurringDays" value="Friday" class="form-checkbox h-5 w-5 text-indigo-600 dark:text-indigo-400">
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Viernes</span>
              </label>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-gray-200">Frecuencia</label>
            <select
              v-model="recurringFrequency"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 sm:text-sm p-2"
            >
              <option value="">Seleccione frecuencia</option>
              <option value="weekly">Semanal</option>
              <option value="monthly">Mensual</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-gray-200">Número de repeticiones</label>
            <input
              v-model.number="repetitionCount"
              type="number"
              min="1"
              max="52"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 sm:text-sm p-2"
              placeholder="Máximo 52 repeticiones"
            >
          </div>
        </div>
      </div>
      <div class="mt-6 flex justify-end space-x-4">
        <button
          @click="closeModal"
          class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500"
        >
          Cancelar
        </button>
        <button
          @click="saveTime"
          class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!tempStartTime || !tempEndTime || (isRecurring && (!recurringFrequency || !repetitionCount || recurringDays.length === 0))"
        >
          Guardar
        </button>
      </div>
    </div>
  </div>
</template>