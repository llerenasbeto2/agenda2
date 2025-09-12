<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import DynamicLayout from '@/Layouts/DynamicLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import BlueButton from '@/Components/BlueButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
  reservations_classrooms: Array,
  classrooms: Array,
  faculties: Array,
  categories: Array,
});

const calendar = ref(null);
let calendarInstance = null;
const selectedDate = ref(null);
const showModal = ref(false);
const showIrregularDatesModal = ref(false);
const repeatEvent = ref(null);
const checkedNames = ref([])

const form = ref({
  startDate: '',
  startTime: '08:00',
  endDate: '',
  endTime: '09:00',
});

const irregularDatesForm = ref({
  dates: [{ date: '', startTime: '08:00', endTime: '09:00' }]
});

const transformReservationsToEvents = (reservations) => {
  if (!reservations) return [];

  return reservations.map(reservation => ({
    id: reservation.id,
    title: reservation.event_title,
    start: reservation.start_datetime,
    end: reservation.end_datetime,
    color: '#4285F4',
  }));
};

const allEvents = computed(() => transformReservationsToEvents(props.reservations_classrooms));

onMounted(() => {
  if (window.FullCalendar) {
    const calendarEl = calendar.value;

    calendarInstance = new window.FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      headerToolbar: {
        left: 'prev,next today reservarEvento',
        center: 'title',
        right: 'fechasIrregulares dayGridMonth,listMonth',
      },
      customButtons: {
        reservarEvento: {
          text: 'Reservar evento',
          click: openModal,
        },
        fechasIrregulares: {
          text: 'Fechas Irregulares',
          click: openIrregularDatesModal,
        }
      },
      events: allEvents.value,
      locale: 'es',
      dateClick: handleDateClick,
      eventClick: handleEventClick,
    });

    calendarInstance.render();

    // Mueve el botón "Reservar evento" al lado de "Today"
    nextTick(() => {
      const todayButton = document.querySelector('.fc-today-button');
      const reservarButton = document.querySelector('.fc-reservarEvento-button');

      if (todayButton && reservarButton) {
        todayButton.parentNode.insertBefore(reservarButton, todayButton.nextSibling);
        
        // Asegurarnos de que el botón esté oculto inicialmente
        reservarButton.style.display = 'none';
      }
    });
  }
});

function handleDateClick(info) {
  selectedDate.value = info.dateStr;

  // Prellenar la fecha en el modal
  form.value.startDate = info.dateStr;
  form.value.endDate = info.dateStr;

  // Mostrar el botón dentro del calendario
  const button = document.querySelector('.fc-reservarEvento-button');
  if (button) {
    button.style.display = 'inline-block';
  }
}

function openModal() {
  showModal.value = true;
  repeatEvent.value = null;
}

function closeModal() {
  showModal.value = false;
}

function openIrregularDatesModal() {
  showIrregularDatesModal.value = true;
  // Reset the form
  irregularDatesForm.value = {
    dates: [{ date: '', startTime: '08:00', endTime: '09:00' }]
  };
}

function addAnotherDate() {
  irregularDatesForm.value.dates.push({ 
    date: '', 
    startTime: '08:00', 
    endTime: '09:00' 
  });
}

function removeDate(index) {
  if (irregularDatesForm.value.dates.length > 1) {
    irregularDatesForm.value.dates.splice(index, 1);
  }
}

function submitIrregularDates() {
  console.log('Fechas irregulares:', irregularDatesForm.value);
  // Aquí puedes agregar la lógica para procesar las fechas
  alert('Fechas irregulares enviadas');
  showIrregularDatesModal.value = false;
}

function submitReservation() {
  console.log('Reserva realizada:', form.value);
  console.log('Repetir evento:', repeatEvent.value);
  alert(`Evento reservado:\nDe: ${form.value.startDate} ${form.value.startTime}\nA: ${form.value.endDate} ${form.value.endTime}`);
  closeModal();
}

function handleEventClick(info) {
  const event = info.event;
  alert(`Evento: ${event.title}\nFecha: ${event.start.toLocaleDateString('es-MX')}`);
}
</script>

<template>
  <DynamicLayout>
    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <p class="mb-6 text-lg">
              Bienvenido a la plataforma de gestión de reservaciones de aulas de la Universidad.
            </p>

            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-700 relative">
              <h2 class="mb-4 text-2xl font-bold">Calendario de Reservaciones</h2>

              <div class="relative">
                <div ref="calendar" class="calendar"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL DE RESERVACIÓN REGULAR -->
    <div v-if="showModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <h3 class="text-xl font-bold mb-4">Reservar Evento</h3>

        <form @submit.prevent="submitReservation">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="start-time" class="block text-sm font-medium">Hora inicial</label>
              <input 
                type="time" id="start-time" v-model="form.startTime" step="1800" 
                class="w-full px-4 py-2 rounded border shadow-sm focus:ring focus:ring-blue-500" required
              />
            </div>

            <div>
              <label for="end-time" class="block text-sm font-medium">Hora final</label>
              <input 
                type="time" id="end-time" v-model="form.endTime" step="1800" 
                class="w-full px-4 py-2 rounded border shadow-sm focus:ring focus:ring-blue-500" required
              />
            </div>
          </div>

          <div class="mt-6 text-center">
            <p class="text-x font-bold mb-4">¿Desea que el evento se repita?</p>
            <div class="flex justify-center space-x-4">
              <BlueButton type="button" @click="repeatEvent = true" 
                :class="['px-4 py-2 rounded', repeatEvent === true ? 'bg-blue-600 text-white' : 'bg-blue-500 text-white']"
              >
                Sí
              </BlueButton>
              <DangerButton 
                type="button" 
                @click="() => {
                  repeatEvent = false;
                  form.repeatFrequency = '';
                  checkedNames = [];
                }" 
                :class="['px-4 py-2 rounded', repeatEvent === false ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700']"
              >
                No
              </DangerButton>
            </div>

            <!-- Sección de frecuencia de repetición (solo visible cuando Sí está seleccionado) -->
            <div v-if="repeatEvent" class="mt-4">
              <h3 class="text-x font-bold mb-4">Frecuencia de repetición:</h3>
              <select 
                v-model="form.repeatFrequency" 
                class="w-full px-4 py-2 rounded border shadow-sm focus:ring focus:ring-blue-500"
              >
                <option value="weekly">Semanal</option>
                <option value="monthly">Mensual</option>
              </select>

              <!-- Seccion de repeticion de días (solo visible si se selecciona weekly o monthly) -->
              <div v-if="form.repeatFrequency === 'weekly' || form.repeatFrequency === 'monthly'" class="mt-4">
                <h3 class="text-x font-bold mb-4">Días de repetición:</h3>
                <div class="flex flex-wrap justify-center gap-4">
                  <div class="flex items-center">
                    <input 
                      type="checkbox" id="mon" value="Monday" v-model="checkedNames" class="mr-2"
                    />
                    <label for="mon">Lunes</label>
                  </div>

                  <div class="flex items-center">
                    <input 
                      type="checkbox" id="tues" value="Tuesday" v-model="checkedNames" class="mr-2"
                    />
                    <label for="tues">Martes</label>
                  </div>

                  <div class="flex items-center">
                    <input 
                      type="checkbox" id="wed" value="Wednesday" v-model="checkedNames" class="mr-2"
                    />
                    <label for="wed">Miércoles</label>
                  </div>

                  <div class="flex items-center">
                    <input 
                      type="checkbox" id="thu" value="Thursday" v-model="checkedNames" class="mr-2"
                    />
                    <label for="thu">Jueves</label>
                  </div>

                  <div class="flex items-center">
                    <input 
                      type="checkbox" id="fri" value="Friday" v-model="checkedNames" class="mr-2"
                    />
                    <label for="fri">Viernes</label>
                  </div>
                </div>

                <h3 class="text-lg font-bold mb-4 mt-4">Finaliza:</h3>

              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-4">
            <DangerButton 
              type="button" @click="closeModal" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500"
            >
              Cancelar
            </DangerButton>
            <PrimaryButton 
              type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
            >
              Aceptar
            </PrimaryButton>
          </div>        
        </form>
      </div>
    </div>

    <!-- MODAL DE FECHAS IRREGULARES -->
    <div v-if="showIrregularDatesModal" class="modal-overlay" @click="showIrregularDatesModal = false">
      <div class="modal-content" @click.stop>
        <h3 class="text-xl font-bold mb-4">Fechas Irregulares</h3>

        <form @submit.prevent="submitIrregularDates">
          <div v-for="(dateEntry, index) in irregularDatesForm.dates" :key="index" class="mb-4 p-4 border rounded">
            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Fecha</label>
                <input 
                  type="date" 
                  v-model="dateEntry.date"
                  class="w-full px-4 py-2 rounded border shadow-sm focus:ring focus:ring-blue-500"
                  required
                />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Hora inicial</label>
                <input 
                  type="time" 
                  v-model="dateEntry.startTime"
                  step="1800"
                  class="w-full px-4 py-2 rounded border shadow-sm focus:ring focus:ring-blue-500"
                  required
                />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Hora final</label>
                <input 
                  type="time" 
                  v-model="dateEntry.endTime"
                  step="1800"
                  class="w-full px-4 py-2 rounded border shadow-sm focus:ring focus:ring-blue-500"
                  required
                />
              </div>
            </div>
            <div class="mt-2 text-right" v-if="irregularDatesForm.dates.length > 1">
              <button 
                type="button" 
                @click="removeDate(index)" 
                class="text-red-500 hover:text-red-700"
              >
                Eliminar fecha
              </button>
            </div>
          </div>

          <div class="mt-4 text-center">
            <BlueButton 
              type="button" 
              @click="addAnotherDate" 
              class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
            >
              Agregar otra fecha
            </BlueButton>
          </div>

          <div class="mt-6 flex justify-end space-x-4">
            <DangerButton 
              type="button" 
              @click="showIrregularDatesModal = false" 
              class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500"
            >
              Cancelar
            </DangerButton>
            <PrimaryButton 
              type="submit" 
              class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
            >
              Guardar
            </PrimaryButton>
          </div>
        </form>
      </div>
    </div>
  </DynamicLayout>
</template>