<!-- Expert_user.vue -->
<script setup>
import { defineProps, defineEmits, ref, watch, onMounted, onUnmounted, nextTick, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import classroom_faculties from '@/Components/agendado/classroom_faculties.vue';
import axios from 'axios';
import Condiciones_requerimientos from '@/Components/agendado/Condiciones_requerimientos.vue';


const props = defineProps({
  formData: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['close']);

const page = usePage();
const isAuthenticated = ref(!!page.props.auth.user);
const userData = page.props.userData || {};
// Estado para controlar la visibilidad del modal
const showModal = ref(false);
// Estado para el checkbox de términos
const termsAccepted = ref(false);

const form = useForm({
  user_id: userData.id || null,
  full_name: userData.name || '',
  email: userData.email || '',
  phone: userData.phone || '',
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
  recurring_days: [],
  recurring_end_date: null,
  irregular_dates: [],
  cost: '0.00',
  is_paid: '0',
  payment_date: null,
});

const categories = ref(props.formData.categories || []);
const municipalities = ref(props.formData.municipalities || []);

// Calendar-related reactive variables
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
const create_events = ref([]);
const existing_reservations = ref([]);
const isLoadingReservations = ref(false);
const conflictMessage = ref('');
const editingEventIndex = ref(null);
const detectedConflicts = ref([]);
// Nueva variable para el input de fecha personalizada
const customDate = ref('');

const timeOptions = ref([
  '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
  '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30',
  '17:00', '17:30', '18:00', '18:30', '19:00', '19:30', '20:00'
]);

// Calendar computed properties
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

// Computed para determinar si el botón de enviar debe estar habilitado
const isSubmitDisabled = computed(() => {
  return !form.start_datetime || !form.end_datetime || !form.classroom_id || 
         !form.municipality_id || !form.category_type || !form.attendees || 
         !form.event_title || !form.phone || !form.full_name || 
         (!isAuthenticated.value && !form.email) || !termsAccepted.value;
});

// Calendar functions
const loadExistingReservations = async () => {
  if (!form.classroom_id) {
    existing_reservations.value = [];
    return;
  }

  isLoadingReservations.value = true;
  try {
    const response = await axios.get('/existing-reservations', {
      params: {
        classroom_id: form.classroom_id
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

const checkForConflicts = async (dates) => {
  if (!form.classroom_id || !dates.length) return { has_conflicts: false, conflicts: [] };

  try {
    const response = await axios.post('/check-reservation-conflicts', {
      classroom_id: form.classroom_id,
      dates: dates
    });
    return response.data;
  } catch (error) {
    console.error('Error checking conflicts:', error);
    return { has_conflicts: false, conflicts: [] };
  }
};

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

const openModal = (day, initialTime = null, eventIndex = null) => {
  selectedDay.value = day;
  modalInitialTime.value = initialTime;
  editingEventIndex.value = eventIndex;
  
  // Establecer la fecha personalizada basada en el día seleccionado
  customDate.value = day.toISOString().split('T')[0];
  
  tempStartTime.value = initialTime || '';
  tempEndTime.value = '';
  isRecurring.value = false;
  recurringDays.value = [];
  recurringFrequency.value = '';
  repetitionCount.value = null;
  conflictMessage.value = '';

  if (eventIndex !== null && create_events.value[eventIndex]) {
    const eventKey = `event_${eventIndex + 1}`;
    const event = create_events.value[eventIndex][eventKey];
    
    const startDateTime = event.start_datetime;
    const endDateTime = event.end_datetime;
    
    tempStartTime.value = startDateTime.split(' ')[1]?.substring(0, 5) || '';
    tempEndTime.value = endDateTime.split(' ')[1]?.substring(0, 5) || '';
    
    if (event.recurring_days !== null && event.recurring_days !== undefined) {
      isRecurring.value = true;
      
      try {
        recurringDays.value = JSON.parse(event.recurring_days) || [];
      } catch (e) {
        console.error('Error parsing recurring_days:', e);
        recurringDays.value = [];
      }
      
      recurringFrequency.value = event.recurring_frequency || '';
      repetitionCount.value = event.repeticion || null;
    }
  }

  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedDay.value = null;
  modalInitialTime.value = null;
  editingEventIndex.value = null;
  tempStartTime.value = '';
  tempEndTime.value = '';
  customDate.value = '';
  isRecurring.value = false;
  recurringDays.value = [];
  recurringFrequency.value = '';
  repetitionCount.value = null;
  conflictMessage.value = '';
};

const handleRecurringChange = (event) => {
  isRecurring.value = event.target.checked;
};

// Nueva función para manejar el cambio de fecha personalizada
const handleCustomDateChange = () => {
  if (customDate.value) {
    const newDate = new Date(customDate.value + 'T00:00:00');
    const dayOfWeek = newDate.getDay();
    
    // Verificar que sea un día de la semana (lunes a viernes)
    if (dayOfWeek >= 1 && dayOfWeek <= 5) {
      selectedDay.value = newDate;
    } else {
      alert('Por favor, selecciona un día de la semana (lunes a viernes).');
      // Resetear al día previamente seleccionado
      if (selectedDay.value) {
        customDate.value = selectedDay.value.toISOString().split('T')[0];
      }
    }
  }
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

const saveTime = async () => {
  // Usar la fecha personalizada si está disponible, sino usar selectedDay
  const dateToUse = customDate.value ? new Date(customDate.value + 'T00:00:00') : selectedDay.value;
  const dateStr = dateToUse.toISOString().split('T')[0];
  const baseDate = new Date(dateStr + 'T00:00:00');

  const start_datetime = `${dateStr} ${tempStartTime.value}:00`;
  const end_datetime = `${dateStr} ${tempEndTime.value}:00`;
  
  let datesToCheck = [{
    date: dateStr,
    start_time: tempStartTime.value,
    end_time: tempEndTime.value
  }];

  const recurringData = {
    is_recurring: isRecurring.value,
    recurring_days: isRecurring.value && recurringDays.value.length > 0 ? JSON.stringify(
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

  let current_irregular_dates = [];
  const startTime = tempStartTime.value;
  const endTime = tempEndTime.value;

  if (isRecurring.value && recurringDays.value.length > 0 && repetitionCount.value) {
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

          if (i === 0) {
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
    const displayText = `${dateStr} ${startTime} - ${endTime}`;
    current_irregular_dates.push({
      date: dateStr,
      startTime: startTime,
      endTime: endTime,
      displayText: displayText
    });
  }

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

  const newEvent = {
    start_datetime: start_datetime,
    end_datetime: end_datetime,
    recurring_days: recurringData.recurring_days,
    recurring_frequency: recurringData.recurring_frequency,
    repeticion: recurringData.repeticion,
    irregular_dates: JSON.stringify(current_irregular_dates),
    has_conflicts: conflictCheck.has_conflicts
  };

  if (editingEventIndex.value !== null) {
    const eventObj = { [`event_${editingEventIndex.value + 1}`]: newEvent };
    create_events.value.splice(editingEventIndex.value, 1, eventObj);
  } else {
    if (isRecurring.value) {
      const existingRecurringIndex = create_events.value.findIndex((eventObj, index) => {
        const eventKey = `event_${index + 1}`;
        const event = eventObj[eventKey];
        return event.recurring_days !== null && event.recurring_days !== undefined;
      });

      if (existingRecurringIndex !== -1) {
        const eventObj = { [`event_${existingRecurringIndex + 1}`]: newEvent };
        create_events.value.splice(existingRecurringIndex, 1, eventObj);
      } else {
        const eventIndex = create_events.value.length;
        const eventObj = { [`event_${eventIndex + 1}`]: newEvent };
        create_events.value = [...create_events.value, eventObj];
      }
    } else {
      const eventIndex = create_events.value.length;
      const eventObj = { [`event_${eventIndex + 1}`]: newEvent };
      create_events.value = [...create_events.value, eventObj];
    }
  }

  // Update form data with consolidated information
  updateFormWithEvents();
  updateCalendarEvents();
  closeModal();
};

const updateFormWithEvents = () => {
  let consolidated_irregular_dates = [];
  create_events.value.forEach((eventObj, index) => {
    const eventKey = `event_${index + 1}`;
    const event = eventObj[eventKey];
    if (event.irregular_dates) {
      const eventDates = JSON.parse(event.irregular_dates);
      consolidated_irregular_dates = consolidated_irregular_dates.concat(eventDates);
    }
  });

  let global_is_recurring = false;
  let global_recurring_days = null;
  let global_recurring_frequency = null;
  let global_repeticion = null;

  for (let i = create_events.value.length - 1; i >= 0; i--) {
    const eventKey = `event_${i + 1}`;
    const event = create_events.value[i][eventKey];
    if (event.recurring_days !== null) {
      global_is_recurring = true;
      global_recurring_days = event.recurring_days;
      global_recurring_frequency = event.recurring_frequency;
      global_repeticion = event.repeticion;
      break;
    }
  }

  // FIX: En lugar de tomar solo el último evento, encontrar las fechas mínima y máxima
  if (create_events.value.length > 0) {
    let allDates = [];
    
    // Recopilar todas las fechas de todos los eventos
    create_events.value.forEach((eventObj, index) => {
      const eventKey = `event_${index + 1}`;
      const event = eventObj[eventKey];
      
      // Agregar la fecha principal del evento
      allDates.push({
        start: new Date(event.start_datetime),
        end: new Date(event.end_datetime)
      });
      
      // Si tiene fechas irregulares, agregarlas también
      if (event.irregular_dates) {
        const irregularDates = JSON.parse(event.irregular_dates);
        irregularDates.forEach(irregular => {
          allDates.push({
            start: new Date(`${irregular.date} ${irregular.startTime}:00`),
            end: new Date(`${irregular.date} ${irregular.endTime}:00`)
          });
        });
      }
    });
    
    // Encontrar la fecha de inicio más temprana y la fecha de fin más tardía
    const earliestStart = allDates.reduce((min, current) => 
      current.start < min ? current.start : min, allDates[0].start
    );
    
    const latestEnd = allDates.reduce((max, current) => 
      current.end > max ? current.end : max, allDates[0].end
    );
    
    // Formatear las fechas para la base de datos (YYYY-MM-DD HH:mm:ss)
    form.start_datetime = earliestStart.toISOString().slice(0, 19).replace('T', ' ');
    form.end_datetime = latestEnd.toISOString().slice(0, 19).replace('T', ' ');
  }

  form.is_recurring = global_is_recurring;
  form.recurring_days = global_recurring_days ? JSON.parse(global_recurring_days) : [];
  form.recurring_frequency = global_recurring_frequency;
  form.repeticion = global_repeticion;
  form.irregular_dates = consolidated_irregular_dates;
};

const editEvent = (index) => {
  const event = create_events.value[index][`event_${index + 1}`];
  const startDateParts = event.start_datetime.split(' ')[0].split('-');
  const day = new Date(startDateParts[0], startDateParts[1] - 1, startDateParts[2]);
  openModal(day, event.start_datetime.split(' ')[1], index);
};

const deleteEvent = (index) => {
  create_events.value.splice(index, 1);
  
  create_events.value = create_events.value.map((eventObj, newIndex) => {
    const oldEventKey = Object.keys(eventObj)[0];
    const eventData = eventObj[oldEventKey];
    return { [`event_${newIndex + 1}`]: eventData };
  });
  
  updateFormWithEvents();
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
    
    create_events.value.forEach((eventObj, index) => {
      const eventKey = `event_${index + 1}`;
      const event = eventObj[eventKey];
      const start = new Date(event.start_datetime);
      const end = new Date(event.end_datetime);

      const eventColor = event.has_conflicts ? '#f59e0b' : '#10b981';
      const eventTitle = event.has_conflicts ? 'Mi Reserva (⚠️ Conflicto)' : 'Mi Reserva';

      events.push({ 
        title: eventTitle, 
        start, 
        end, 
        color: eventColor,
        classNames: ['user-event'],
        borderColor: event.has_conflicts ? '#dc2626' : eventColor
      });

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

// Initialize calendar
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
        initialDate: new Date().toISOString().split('T')[0],
        events: [],
        eventClick: function(info) {
          if (info.event.classNames.includes('existing-event')) {
            alert(`Reserva existente:\n${info.event.title}\nOrganizador: ${info.event.extendedProps.organizer}`);
          }
        }
      });
      calendar.render();
    }
  });
});

onUnmounted(() => {
  if (calendar) {
    calendar.destroy();
    calendar = null;
  }
});

// Watch for classroom changes
watch(() => form.classroom_id, (newClassroomId) => {
  if (newClassroomId) {
    loadExistingReservations();
  } else {
    existing_reservations.value = [];
    create_events.value = [];
    updateCalendarEvents();
  }
});

// Original form submission logic
const submit = () => {
  if (!termsAccepted.value) {
    alert('Debes aceptar los términos y condiciones antes de enviar la solicitud.');
    return;
  }

  if (!form.full_name || !form.phone || (!isAuthenticated.value && !form.email) || 
      !form.event_title || !form.attendees || !form.category_type || 
      !form.municipality_id || !form.faculty_id || !form.classroom_id || 
      !form.start_datetime || !form.end_datetime) {
    alert('Por favor, completa todos los campos requeridos antes de enviar.');
    return;
  }

  form.post(route('myreservationsclassroom.store'), {
    onSuccess: () => {
      create_events.value = [];
      alert('Reservación enviada con éxito!');
      emit('close');
    },
    onError: (errors) => {
      console.error('Error al enviar:', errors);
      alert('Ocurrió un error al enviar la reservación. Revisa la consola para más detalles.');
    },
  });
};

const closeMainModal = () => {
  form.reset();
  create_events.value = [];
  termsAccepted.value = false;
  emit('close');
};

// Reset events when municipality or faculty changes
watch(() => form.municipality_id, (newVal) => {
  if (newVal) {
    form.faculty_id = null;
    form.classroom_id = null;
    create_events.value = [];
    form.irregular_dates = [];
    form.recurring_days = [];
  }
});

watch(() => form.faculty_id, (newVal) => {
  if (newVal) {
    form.classroom_id = null;
    create_events.value = [];
    form.irregular_dates = [];
    form.recurring_days = [];
  }
});
</script>

<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="closeMainModal">
    <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-xl w-full max-w-6xl max-h-[95vh] overflow-y-auto">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Solicitud Directa de Reservación</h2>
      
      <form @submit.prevent="submit" class="space-y-8">
        <!-- Sección: Información Personal -->
        <div class="border-b pb-4">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Información Personal</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre Completo *</label>
              <input id="full_name" v-model="form.full_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
            </div>
            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono *</label>
              <input id="phone" v-model="form.phone" type="tel" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
            </div>
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Correo Electrónico *</label>
              <input id="email" v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
            </div>
          </div>
        </div>

        <!-- Sección: Información del Evento -->
        <div class="border-b pb-4">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Información del Evento</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="event_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título del Evento *</label>
              <input id="event_title" v-model="form.event_title" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
            </div>
            <div>
              <label for="attendees" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número de Participantes *</label>
              <input id="attendees" v-model="form.attendees" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
            </div>
            <div>
              <label for="category_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoría *</label>
              <select id="category_type" v-model="form.category_type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Seleccione una categoría</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
              </select>
            </div>
          </div>
          <div class="mt-4">
            <label for="requirements" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Requerimientos Adicionales</label>
            <textarea id="requirements" v-model="form.requirements" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
          </div>
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
          Acepto los <a href="#" @click.prevent="showModal = true" class="text-indigo-600 hover:underline">términos y condiciones</a> de uso y política de reservas. *
        </label>
      </div>

      <!-- Modal para términos y condiciones -->
      <Condiciones_requerimientos v-if="showModal" @close="showModal = false" />

        <!-- Sección: Ubicación -->
        <div class="border-b pb-4">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Ubicación</h3>
          <div class="mb-4">
            <label for="municipality_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Municipio *</label>
            <select id="municipality_id" v-model="form.municipality_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
              <option value="">Seleccione un municipio</option>
              <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">{{ municipality.name }}</option>
            </select>
          </div>
          <classroom_faculties
            v-if="form.municipality_id"
            :faculties="props.formData.faculties"
            :municipality-id="form.municipality_id"
            :initial-classrooms="props.formData.classrooms || []"
            :faculty-id="form.faculty_id"
            :classroom-id="form.classroom_id"
            @update:faculty-id="form.faculty_id = $event"
            @update:classroom-id="form.classroom_id = $event"
          />
        </div>

        <!-- Sección: Fecha y Hora con Calendario Integrado -->
        <div v-if="form.classroom_id" class="border-b pb-4">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Fecha y Hora *</h3>
          
          <!-- Calendario Integrado -->
          <div class="flex space-x-6">
            <!-- Columna izquierda: Selección de días -->
            <div class="w-1/4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md">
              <h4 class="text-md font-semibold text-gray-900 dark:text-gray-200 mb-4">Seleccionar Día</h4>
              
              <!-- Indicador de carga -->
              <div v-if="isLoadingReservations" class="flex items-center justify-center p-4">
                <div class="loading-spinner"></div>
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Cargando reservaciones...</span>
              </div>
              
              <!-- Mensaje si no hay aula seleccionada -->
              <div v-else-if="!form.classroom_id" class="p-4 text-center text-gray-500 dark:text-gray-400 text-sm">
                Seleccione un aula en el paso anterior para ver disponibilidad
              </div>
              
              <!-- Botones de días -->
              <div v-else class="space-y-2">
                <button
                  v-for="(day, index) in getCurrentWeekDates"
                  :key="day"
                  @click.prevent="openModal(day)"
                  type="button"
                  class="w-full py-2 px-4 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-700 dark:hover:bg-indigo-800 disabled:opacity-50 disabled:cursor-not-allowed"
                  :disabled="!form.classroom_id"
                >
                  {{ day.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'short' }) }}
                </button>
              </div>

              <!-- Leyenda y alertas de conflictos -->
              <div v-if="form.classroom_id" class="mt-4">
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
                      <h5 class="font-semibold mb-2">Conflictos de Horario Detectados</h5>
                      <p class="text-sm mb-2">
                        Las siguientes reservaciones tienen conflictos con horarios ya ocupados. Tu solicitud será enviada para revisión manual:
                      </p>
                      <ul class="text-xs space-y-1">
                        <li v-for="conflict in detectedConflicts" :key="`${conflict.date}-${conflict.requested_start}`" class="pl-2 border-l-2 border-red-300">
                          <strong>{{ conflict.date }}</strong> {{ conflict.requested_start }}-{{ conflict.requested_end }}
                          <br>
                          <span class="text-red-700 dark:text-red-300">Conflicto con: {{ conflict.existing_reservation.title }}</span>
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
                <h4 class="text-md font-semibold text-gray-900 dark:text-gray-200 mb-2">Días a Solicitar</h4>
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
                        @click.prevent="editEvent(index)"
                        type="button"
                        class="px-2 py-1 bg-yellow-500 text-white rounded-md hover:bg-yellow-600"
                      >
                        Modificar
                      </button>
                      <button
                        @click.prevent="deleteEvent(index)"
                        type="button"
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
                <h4 class="text-md font-semibold text-gray-900 dark:text-gray-200 mb-2">Reservaciones Existentes en esta Aula</h4>
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
        </div>

        <!-- Botones -->
        <div class="flex justify-end space-x-4 mt-6">
          <button type="button" @click="closeMainModal" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">
            Cancelar
          </button>
          <button 
            type="submit" 
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed" 
            :disabled="isSubmitDisabled"
          >
            Enviar Solicitud
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal para seleccionar horario -->
  <div v-if="isModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96 overflow-auto" :style="{ maxHeight: '90vh' }">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200 mb-4">
        Seleccionar Horario para {{ selectedDay?.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
      </h3>
      
      <!-- Campo para seleccionar fecha personalizada -->
      <div class="mb-4">
        <label for="custom_date" class="block text-sm font-medium text-gray-900 dark:text-gray-200 mb-2">Cambiar Fecha (solo días de la semana)</label>
        <input
          id="custom_date"
          v-model="customDate"
          @change="handleCustomDateChange"
          type="date"
          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 sm:text-sm p-2"
        >
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          Solo se permiten días de lunes a viernes
        </p>
      </div>
      
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
          type="button"
          class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500"
        >
          Cancelar
        </button>
        <button
          @click="saveTime"
          type="button"
          class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!tempStartTime || !tempEndTime || (isRecurring && (!recurringFrequency || !repetitionCount || recurringDays.length === 0))"
        >
          Guardar
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Estilos para mantener consistencia con el diseño de Create.vue */
form {
  max-width: 100%;
}

.space-y-8 > * + * {
  margin-top: 2rem;
}

.border-b {
  border-bottom: 1px solid #e5e7eb;
}

.dark .border-b {
  border-bottom: 1px solid #374151;
}

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