<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import AdminAreaLayout from '@/Layouts/AdminAreaLayout.vue';
import AlertAdeudo from '@/Components/AlertAdeudo.vue';
import AlertCuenta from '@/Components/AlertCuenta.vue';
import AlertCondiciones from '@/Components/AlertCondiciones.vue';
import ViewReservationModal from '@/Components/ViewReservationModal.vue';
import EditReservationModal from '@/Components/Area/EditReservationModal.vue';
import DeleteReservationModal from '@/Components/Area/DeleteReservationModal.vue';
import Comentarios from '@/Components/Area/Comentarios.vue';
import PaymentDetails from '@/Components/Area/PaymentDetails.vue';
import StatusButtons from '@/Components/Area/StatusButtons.vue';
import Pagination from '@/Components/Pagination.vue';

const showPaymentModal = ref(false);
const selectedStatus = ref('');
const selectedDate = ref('');
const showIngresosModal = ref(false);
const showIngresosModalcuenta = ref(false);
const showIngresosModalcondiciones = ref(false);
const showViewModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showStatusDropdown = ref(null);
const selectedReservation = ref(null);
const showcommentModal = ref(false);
const filters = ref({ event_title: '' });
const currentPage = ref(1);

const props = defineProps({
  reservaciones: Array,
  faculties: Array,
  classrooms: Array,
  categorie: Array,
  municipalities: Array,
  auth: Object,
  filters: Object,
});

onMounted(() => {
  if (props.filters?.event_title) {
    filters.value.event_title = props.filters.event_title;
  }
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.status-dropdown-container')) {
      closeStatusDropdowns();
    }
  });
});

const filteredReservations = computed(() => {
  return props.reservaciones.filter(reservacion => {
    const statusMatch = !selectedStatus.value || reservacion.status === selectedStatus.value;
    const classroomMatch = reservacion.classroom_id === props.auth.user.responsible;
    const dateMatch = !selectedDate.value || 
      new Date(reservacion.start_datetime).toISOString().split('T')[0] === selectedDate.value;
    return statusMatch && classroomMatch && dateMatch;
  }); 
});

watch([selectedStatus, selectedDate, () => filters.value.event_title], () => {
  currentPage.value = 1;
});

const search = () => {
  router.get(route('admin.area.dashboard'), {
    event_title: filters.value.event_title,
    date: selectedDate.value,
  }, {
    preserveState: true,
    replace: true,
  });
};

const resetFilters = () => {
  selectedStatus.value = '';
  selectedDate.value = '';
  filters.value.event_title = '';
  search();
};

let searchTimeout = null;
watch(() => filters.value.event_title, (newValue) => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    search();
  }, 300);
});

const handleStatusChanged = (data) => {
  console.log('Status changed:', data);
  const reservation = props.reservaciones.find(r => r.id === data.id);
  if (reservation) {
    reservation.status = data.status;
  }
};

const handleCommentSent = (comment) => {
  console.log('Comment sent:', comment);
};

const openPaymentModal = (reserva) => {
  selectedReservation.value = reserva;
  showPaymentModal.value = true;
};

const handlePaymentUpdate = (paymentData) => {
  console.log('Detalles de pago actualizados:', paymentData);
};

const openIngresosModal = () => {
  showIngresosModal.value = true;
};

const closeIngresosModal = () => {
  showIngresosModal.value = false;
};

const openIngresosModalcuenta = () => {
  showIngresosModalcuenta.value = true;
};

const closeIngresosModalcuenta = () => {
  showIngresosModalcuenta.value = false;
};

const openIngresosModalcondiciones = () => {
  showIngresosModalcondiciones.value = true;
};

const closeIngresosModalcondiciones = () => {
  showIngresosModalcondiciones.value = false;
};

const openViewModal = (reservacion) => {
  selectedReservation.value = reservacion;
  showViewModal.value = true;
};

const closeViewModal = () => {
  showViewModal.value = false;
  selectedReservation.value = null;
};

const openCommentModal = (reservation) => {
  selectedReservation.value = reservation;
  showcommentModal.value = true;
};

const closeCommentModal = () => {
  showcommentModal.value = false;
  selectedReservation.value = null;
};

const openEditModal = (reservacion) => {
  selectedReservation.value = reservacion;
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
  selectedReservation.value = null;
};

const openDeleteModal = (reservacion) => {
  selectedReservation.value = reservacion;
  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  selectedReservation.value = null;
};

const closeStatusDropdowns = () => {
  showStatusDropdown.value = null;
};

const formatFecha = (fecha) => {
  if (!fecha) return '';
  try {
    const date = new Date(fecha);
    if (isNaN(date.getTime())) {
      console.error('Fecha inválida:', fecha);
      return 'Fecha inválida';
    }
    const options = { 
      day: '2-digit', 
      month: '2-digit', 
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
      timeZone: 'UTC'
    };
    return date.toLocaleString('es-MX', options);
  } catch (error) {
    console.error('Error al formatear fecha:', error, fecha);
    return 'Fecha inválida';
  }
};

const eliminarReservacion = (id) => {
  openDeleteModal(props.reservaciones.find(r => r.id === id));
};
</script>

<template>
  <Head title="Agenda de Escenarios Educativos" />

  <AdminAreaLayout>
    <template #header>
      <div class="flex justify-between items-center w-full">
        <div class="flex items-center">
          <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
            Universidad de Colima | Agenda de Escenarios Educativos
          </h2>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-7xl px-2">
        <!-- Top navigation links -->
        <div class="flex justify-between mb-4">
          <div class="text-sm ml-auto flex justify-end">
            <a href="#" @click.prevent="openIngresosModalcondiciones" class="text-blue-600 hover:underline mr-4">Incidencias</a> |
            <a href="#" @click.prevent="openIngresosModalcuenta" class="text-blue-600 hover:underline mx-4">Cuenta</a> |
            <a href="#" @click.prevent="openIngresosModal" class="text-blue-600 hover:underline ml-4">Ingresos/Adeudos</a>
          </div>
        </div>

        <!-- Filtros y búsqueda -->
        <div class="mb-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
          <h3 class="font-semibold text-lg mb-2 text-gray-800 dark:text-gray-200">Filtros</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm text-gray-700 dark:text-gray-300">Estado</label>
              <select 
                v-model="selectedStatus"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600"
              >
                <option value="">Todos</option>
                <option value="Pendiente">Pendiente</option>
                <option value="Aprobado">Aprobado</option>
                <option value="Rechazado">Rechazado</option>
                <option value="Cancelado">Cancelado</option>
                <option value="No_realizado">No Realizado</option>
                <option value="Realizado">Realizado</option>
              </select>
            </div>
            <div>
              <label class="block text-sm text-gray-700 dark:text-gray-300">Fecha</label>
              <input 
                v-model="selectedDate" 
                type="date" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600"
              >
            </div>
            <div>
              <label class="block text-sm text-gray-700 dark:text-gray-300">Buscar por título</label>
              <div class="flex items-center">
                <input 
                  v-model="filters.event_title" 
                  placeholder="Título de la reservación..." 
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:root focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600"
                >
                <button 
                  @click="resetFilters" 
                  class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded ml-2"
                >
                  Limpiar
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla de reservaciones con paginación -->
        <Pagination
          :items="filteredReservations"
          :items-per-page="5"
          v-model:current-page="currentPage"
        >
          <template #default="{ paginatedItems }">
            <div class="overflow-x-auto">
              <table class="min-w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden mt-4">
                <thead>
                  <tr class="bg-gray-100 dark:bg-gray-600 border-b dark:border-gray-500">
                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200 cursor-pointer">
                      <div class="flex items-center">
                        <span>Título</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                        </svg>
                      </div>
                    </th>
                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Estado</th>
                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Categoría</th>
                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Fecha</th>
                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Sala</th>
                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Opciones</th>
                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Maestro</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="reservacion in paginatedItems" :key="reservacion.id" class="border-b dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ reservacion.event_title }}</td>
                    <td class="py-2 px-4">
                      <StatusButtons 
                        :reservation="reservacion" 
                        @status-changed="handleStatusChanged" 
                      />
                    </td>
                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ reservacion.category ? reservacion.category.name : 'Clase' }}</td>
                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ formatFecha(reservacion.start_datetime) }}</td>
                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ reservacion.classroom ? reservacion.classroom.name : 'Aula DGRE' }}</td>
                    <td class="py-2 px-4 flex gap-2">
                      <button @click="openViewModal(reservacion)" 
                              class="bg-blue-500 hover:bg-blue-700 text-white font-bold p-2 rounded-full text-sm" 
                              title="Ver">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                      </button>
                      <button @click="openEditModal(reservacion)" 
                              class="bg-green-500 hover:bg-green-700 text-white font-bold p-2 rounded-full text-sm"
                              title="Editar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button @click="eliminarReservacion(reservacion.id)" 
                              class="bg-red-500 hover:bg-red-700 text-white font-bold p-2 rounded-full text-sm"
                              title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                      <button @click="openCommentModal(reservacion)"  
                              class="bg-blue-500 hover:bg-blue-700 text-white font-bold p-2 rounded-full text-sm"
                              title="Comentarios">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                      </button>
                      <button @click="openPaymentModal(reservacion)"
                              class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold p-2 rounded-full text-sm"
                              title="Pagos">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-currency-dollar" viewBox="0 0 16 16">
                          <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73z"/>
                        </svg>
                      </button>
                    </td>
                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ reservacion.full_name || 'Mike' }}</td>
                  </tr>
                  <tr v-if="paginatedItems.length === 0">
                    <td colspan="7" class="py-4 px-4 text-center text-gray-500 dark:text-gray-400">
                      No se encontraron reservaciones para la facultad seleccionada
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </Pagination>
      </div>
    </div>

    <!-- Modales -->
    <AlertAdeudo 
      :show="showIngresosModal" 
      @close="closeIngresosModal" 
    />
    <AlertCuenta 
      :show="showIngresosModalcuenta" 
      @close="closeIngresosModalcuenta" 
    />
    <AlertCondiciones
      :show="showIngresosModalcondiciones" 
      @close="closeIngresosModalcondiciones" 
    />
    <ViewReservationModal
      :show="showViewModal"
      :reservation="selectedReservation"
      @close="closeViewModal"
    />
    <Comentarios
      v-if="selectedReservation"
      :show="showcommentModal" 
      :reservation-id="selectedReservation.id"
      @close="closeCommentModal"
      @send-comment="handleCommentSent"
    />
    <PaymentDetails
      v-if="selectedReservation"
      :show="showPaymentModal"
      :reservation-id="selectedReservation.id"
      @close="showPaymentModal = false"
      @update-payment="handlePaymentUpdate"
    />
    <EditReservationModal
      v-if="showEditModal"
      :show="showEditModal"
      :reservation="selectedReservation"
      :faculties="props.faculties"
      :classrooms="props.classrooms"
      :categorie="props.categorie"
      :municipalities="props.municipalities"
      :responsibleId="props.auth.user.responsible"
      @close="closeEditModal"
    />
    <DeleteReservationModal
      v-if="showDeleteModal"
      :show="showDeleteModal"
      :reservation="selectedReservation"
      @close="closeDeleteModal"
    />
  </AdminAreaLayout>
</template>