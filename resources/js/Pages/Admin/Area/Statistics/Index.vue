<script setup>
import AdminAreaLayout from '@/Layouts/AdminAreaLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Paleta de colores para los estados
const statusColors = {
  Aprobado: '#10B981', // Verde
  Pendiente: '#F59E0B', // Amarillo
  Rechazado: '#EF4444', // Rojo
  Cancelado: '#6B7280', // Gris
  No_realizado: '#F97316', // Naranja
  Realizado: '#3B82F6' // Azul
};

// Helper function to get the first and last day of the current year
const getDefaultDateRange = () => {
  const today = new Date();
  const firstDay = new Date(today.getFullYear(), 0, 1); // January 1st
  const lastDay = new Date(today.getFullYear(), 11, 31); // December 31st
  return {
    start: firstDay.toISOString().split('T')[0], // Format: YYYY-MM-DD
    end: lastDay.toISOString().split('T')[0]
  };
};

const props = defineProps({
  classrooms: { type: Array, required: true },
  reservations_classrooms: { type: Array, required: true },
  faculties: { type: Array, required: true }
});

const page = usePage();

// Compute user's municipality and responsible area
const isEstado = computed(() => page.props.auth.user?.municipality);
const isResp = computed(() => page.props.auth.user?.responsible);

// Classroom and faculty details
const classroomN = computed(() => {
  return props.classrooms.find(data => Number(data.id) === Number(isResp.value)) || {};
});

const facultadN = computed(() => {
  if (!classroomN.value?.faculty_id) return {};
  return props.faculties.find(info => Number(info.id) === Number(classroomN.value.faculty_id)) || {};
});

// Filter controls with default current year range
const { start, end } = getDefaultDateRange();
const startDate = ref(start);
const endDate = ref(end);
const selectedStatus = ref('Todos');
const statusOptions = [
  'Todos',
  'Aprobado',
  'Pendiente',
  'Rechazado',
  'Cancelado',
  'No_realizado',
  'Realizado'
];

// Validate date range
const isValidDateRange = computed(() => {
  if (!startDate.value || !endDate.value) return true;
  return new Date(endDate.value) >= new Date(startDate.value);
});

// Properly filtered reservations for the specific classroom, faculty, date range, and status
const filteredReservations = computed(() => {
  if (!isResp.value || !facultadN.value?.id) return [];

  let filtered = props.reservations_classrooms.filter(
    (res) =>
      Number(res.classroom_id) === Number(isResp.value) &&
      Number(res.faculty_id) === Number(facultadN.value.id)
  );

  // Apply date range filter
  if (startDate.value && endDate.value && isValidDateRange.value) {
    const start = new Date(startDate.value);
    start.setHours(0, 0, 0, 0); // Normalize to start of day
    const end = new Date(endDate.value);
    end.setHours(23, 59, 59, 999); // Normalize to end of day

    filtered = filtered.filter((res) => {
      let eventDates = [];

      // Handle irregular dates
      if (res.irregular_dates) {
        try {
          const irregularDates =
            typeof res.irregular_dates === 'string'
              ? JSON.parse(res.irregular_dates)
              : res.irregular_dates;
          if (Array.isArray(irregularDates)) {
            eventDates = irregularDates
              .map((item) => new Date(item.date))
              .filter((date) => !isNaN(date.getTime())); // Ensure valid dates
          }
        } catch (e) {
          console.error(`Error parsing irregular dates for reservation ${res.id}:`, e);
        }
      }

      // Handle single event (non-recurring, no irregular dates)
      if (!res.is_recurring && !res.irregular_dates && res.start_datetime) {
        const singleDate = new Date(res.start_datetime);
        if (!isNaN(singleDate.getTime())) {
          eventDates.push(singleDate);
        }
      }

      // Handle recurring events (weekly or monthly)
      if (
        res.is_recurring &&
        res.start_datetime &&
        res.recurring_end_date &&
        res.recurring_frequency
      ) {
        try {
          const startEvent = new Date(res.start_datetime);
          const endRecurring = new Date(res.recurring_end_date);
          const frequency = res.recurring_frequency.toLowerCase();
          let recurringDays =
            typeof res.recurring_days === 'string'
              ? JSON.parse(res.recurring_days)
              : res.recurring_days;

          // Convert day names to numbers (e.g., "Tuesday" -> 2)
          const dayNameToNumber = {
            Sunday: 0,
            Monday: 1,
            Tuesday: 2,
            Wednesday: 3,
            Thursday: 4,
            Friday: 5,
            Saturday: 6,
          };
          if (Array.isArray(recurringDays)) {
            recurringDays = recurringDays.map(
              (day) => dayNameToNumber[day] ?? parseInt(day)
            );
          }

          let currentDate = new Date(startEvent);

          if (frequency === 'weekly' && Array.isArray(recurringDays)) {
            while (currentDate <= endRecurring) {
              if (recurringDays.includes(currentDate.getDay())) {
                eventDates.push(new Date(currentDate));
              }
              currentDate.setDate(currentDate.getDate() + 1);
            }
          } else if (frequency === 'monthly') {
            // For monthly, repeat on the same day of the month
            const dayOfMonth = startEvent.getDate();
            while (currentDate <= endRecurring) {
              if (currentDate.getDate() === dayOfMonth) {
                eventDates.push(new Date(currentDate));
              }
              currentDate.setDate(currentDate.getDate() + 1);
            }
          }
        } catch (e) {
          console.error(`Error calculating recurring dates for reservation ${res.id}:`, e);
        }
      }

      // Check if any event date falls within the selected range
      const inRange = eventDates.some((date) => date >= start && date <= end);
      return inRange;
    });
  }

  // Apply status filter
  if (selectedStatus.value !== 'Todos') {
    const normalizedSelectedStatus = normalizeStatus(selectedStatus.value);
    filtered = filtered.filter(
      (res) => normalizeStatus(res.status) === normalizedSelectedStatus
    );
  }

  return filtered;
});

// Validate if there are enough data to render statistics
const hasValidData = computed(() => {
  return (
    props.classrooms?.length > 0 &&
    props.reservations_classrooms?.length > 0 &&
    props.faculties?.length > 0 &&
    filteredReservations.value.length > 0
  );
});

// Calculate repetitions for a reservation
const calculateRepetitions = (reservation) => {
  if (startDate.value && endDate.value && isValidDateRange.value) {
    const start = new Date(startDate.value);
    start.setHours(0, 0, 0, 0);
    const end = new Date(endDate.value);
    end.setHours(23, 59, 59, 999);

    // Handle irregular dates
    if (reservation.irregular_dates) {
      try {
        let irregularDates =
          typeof reservation.irregular_dates === 'string'
            ? JSON.parse(reservation.irregular_dates)
            : reservation.irregular_dates;
        if (Array.isArray(irregularDates)) {
          const count = irregularDates.filter((item) => {
            const eventDate = new Date(item.date);
            return !isNaN(eventDate.getTime()) && eventDate >= start && eventDate <= end;
          }).length;
          return count;
        }
      } catch (e) {
        console.error(
          `Error parsing irregular dates for reservation ${reservation.id}:`,
          e
        );
        return 0;
      }
    }

    // Handle single event
    if (!reservation.is_recurring && !reservation.irregular_dates && reservation.start_datetime) {
      const eventDate = new Date(reservation.start_datetime);
      return !isNaN(eventDate.getTime()) && eventDate >= start && eventDate <= end ? 1 : 0;
    }

    // Handle recurring events
    if (
      reservation.is_recurring &&
      reservation.start_datetime &&
      reservation.recurring_end_date &&
      reservation.recurring_frequency
    ) {
      try {
        const startEvent = new Date(reservation.start_datetime);
        const endRecurring = new Date(reservation.recurring_end_date);
        const frequency = reservation.recurring_frequency.toLowerCase();
        let recurringDays =
          typeof reservation.recurring_days === 'string'
            ? JSON.parse(reservation.recurring_days)
            : reservation.recurring_days;

        // Convert day names to numbers
        const dayNameToNumber = {
          Sunday: 0,
          Monday: 1,
          Tuesday: 2,
          Wednesday: 3,
          Thursday: 4,
          Friday: 5,
          Saturday: 6,
        };
        if (Array.isArray(recurringDays)) {
          recurringDays = recurringDays.map(
            (day) => dayNameToNumber[day] ?? parseInt(day)
          );
        }

        let count = 0;
        let currentDate = new Date(startEvent);

        if (frequency === 'weekly' && Array.isArray(recurringDays)) {
          while (currentDate <= endRecurring) {
            if (
              recurringDays.includes(currentDate.getDay()) &&
              currentDate >= start &&
              currentDate <= end
            ) {
              count++;
            }
            currentDate.setDate(currentDate.getDate() + 1);
          }
        } else if (frequency === 'monthly') {
          const dayOfMonth = startEvent.getDate();
          while (currentDate <= endRecurring) {
            if (
              currentDate.getDate() === dayOfMonth &&
              currentDate >= start &&
              currentDate <= end
            ) {
              count++;
            }
            currentDate.setDate(currentDate.getDate() + 1);
          }
        }
        return count;
      } catch (e) {
        console.error(
          `Error calculating recurring events for reservation ${reservation.id}:`,
          e
        );
        return 0;
      }
    }
  }

  // Default case: single event or no valid range
  return reservation.irregular_dates
    ? JSON.parse(reservation.irregular_dates)?.length || 1
    : reservation.is_recurring
    ? Number(reservation.repeticion) || 1
    : 1;
};

// Normalize status case for consistent comparison
const normalizeStatus = (status) => {
  if (!status) return '';
  return status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
};

// Calculate statistics by status
const statsByStatus = computed(() => {
  const stats = {
    Aprobado: { count: 0, attendees: 0 },
    Pendiente: { count: 0, attendees: 0 },
    Rechazado: { count: 0, attendees: 0 },
    Cancelado: { count: 0, attendees: 0 },
    No_realizado: { count: 0, attendees: 0 },
    Realizado: { count: 0, attendees: 0 }
  };

  if (filteredReservations.value.length === 0) return stats;

  filteredReservations.value.forEach(res => {
    const normalizedStatus = normalizeStatus(res.status);
    if (!normalizedStatus || !stats[normalizedStatus]) return;

    const repetitions = calculateRepetitions(res);
    const attendeesPerEvent = Number(res.attendees) || 0;

    stats[normalizedStatus].count += repetitions;
    stats[normalizedStatus].attendees += attendeesPerEvent * repetitions;
  });

  return stats;
});

// Calculate total statistics
const totalStats = computed(() => {
  const totals = {
    totalEvents: 0,
    totalAttendees: 0,
    byStatus: {}
  };

  Object.entries(statsByStatus.value).forEach(([status, data]) => {
    if (data.count > 0) {
      totals.totalEvents += data.count;
      totals.totalAttendees += data.attendees;
      totals.byStatus[status] = {
        events: data.count,
        attendees: data.attendees
      };
    }
  });

  return totals;
});

// References for charts
const eventChartContainer = ref(null);
const attendeesChartContainer = ref(null);

// Debug function to verify filtering
const debugFilter = () => {
  console.log({
    activeClassroomId: isResp.value,
    selectedClassroom: classroomN.value,
    selectedFaculty: facultadN.value,
    filteredReservationsCount: filteredReservations.value.length,
    startDate: startDate.value,
    endDate: endDate.value,
    selectedStatus: selectedStatus.value,
    statsByStatus: statsByStatus.value,
    reservations: props.reservations_classrooms?.map(r => ({
      id: r.id,
      faculty_id: r.faculty_id,
      classroom_id: r.classroom_id,
      status: r.status,
      start_datetime: r.start_datetime,
      is_recurring: r.is_recurring,
      recurring_end_date: r.recurring_end_date,
      recurring_frequency: r.recurring_frequency,
      recurring_days: r.recurring_days,
      irregular_dates: r.irregular_dates,
      attendees: r.attendees
    }))
  });
};

// Render event chart function
const renderEventChart = () => {
  if (!hasValidData.value || !eventChartContainer.value) return;

  debugFilter();

  const chartData = Object.entries(statsByStatus.value)
    .filter(([_, data]) => data.count > 0)
    .map(([status, data]) => ({
      name: status,
      y: data.count,
      attendees: data.attendees,
      color: statusColors[status] || '#000000' // Default color if not defined
    }));

  if (chartData.length === 0) {
    console.log('No chart data to render for events');
    return;
  }

  Highcharts.chart(eventChartContainer.value, {
    chart: { type: 'pie' },
    exporting: {
      enabled: true,
      filename: `eventos-por-Aula-en-${facultadN.value?.name || 'Desconocido'}`,
      buttons: {
        contextButton: {
          menuItems: [
            'printChart',
            'downloadPNG',
            'downloadJPEG',
            'downloadPDF',
            'downloadSVG',
            'downloadCSV',
            'downloadXLS'
          ]
        }
      }
    },
    title: {
      text: `Eventos de la Aula - ${classroomN.value?.name || 'Desconocido'}`
    },
    subtitle: {
      text: `Aula: ${classroomN.value?.name || 'Desconocida'} (${startDate.value} al ${endDate.value})`
    },
    tooltip: {
      pointFormat: '<b>{point.name}</b><br>{series.name}: <b>{point.y}</b><br>Porcentaje: <b>{point.percentage:.1f}%</b>'
    },
    plotOptions: {
      series: {
        allowPointSelect: true,
        cursor: 'pointer',
        dataLabels: [
          { enabled: true, distance: 20, format: '{point.name}' },
          {
            enabled: true,
            distance: -40,
            format: '{point.percentage:.1f}%',
            style: { fontSize: '1.2em', textOutline: 'none', opacity: 0.7 },
            filter: { operator: '>', property: 'percentage', value: 5 }
          }
        ]
      }
    },
    series: [{
      name: 'Eventos',
      colorByPoint: false, // Disable automatic colors
      data: chartData
    }]
  });
};

// Render attendees chart function
const renderAttendeesChart = () => {
  if (!hasValidData.value || !attendeesChartContainer.value) return;

  const statuses = Object.keys(statsByStatus.value);
  const attendeesData = Object.values(statsByStatus.value).map((data, index) => ({
    y: data.attendees,
    color: statusColors[statuses[index]] || '#000000'
  }));
  const eventsData = Object.values(statsByStatus.value).map((data, index) => ({
    y: data.count,
    color: statusColors[statuses[index]] || '#000000'
  }));

  if (statuses.length === 0) {
    console.log('No chart data to render for attendees and events');
    return;
  }

  Highcharts.chart(attendeesChartContainer.value, {
    chart: { type: 'column' },
    exporting: {
      enabled: true,
      filename: `asistentes-y-eventos-de-la-Aula-${classroomN.value?.name || 'Desconocido'}`,
      buttons: {
        contextButton: {
          menuItems: [
            'printChart',
            'downloadPNG',
            'downloadJPEG',
            'downloadPDF',
            'downloadSVG',
            'downloadCSV',
            'downloadXLS'
          ]
        }
      }
    },
    title: {
      text: `Asistentes y Eventos en la Aula - ${classroomN.value?.name || 'Desconocido'}`
    },
    subtitle: {
      text: `Aula: ${classroomN.value?.name || 'Desconocida'} (${startDate.value} al ${endDate.value})`
    },
    xAxis: { categories: statuses, crosshair: true },
    yAxis: [
      {
        min: 0,
        title: { text: 'Número de Asistentes' },
        labels: { style: { color: '#000000' } }
      },
      {
        min: 0,
        title: { text: 'Número de Eventos' },
        opposite: true,
        labels: { style: { color: '#000000' } }
      }
    ],
    tooltip: {
      pointFormatter: function () {
        return `<tr><td style="color:${this.series.color};padding:0">${this.series.name}: </td>` +
               `<td style="padding:0"><b>${this.y}</b></td></tr>`;
      },
      footerFormat: '</table>',
      shared: true,
      useHTML: true
    },
    plotOptions: {
      column: {
        pointPadding: 0.1, // Reducir espacio entre barras
        groupPadding: 0.2,
        borderWidth: 0,
        dataLabels: {
          enabled: true,
          format: '{point.y}',
          style: { fontWeight: 'bold' }
        }
      }
    },
    series: [
      {
        name: 'Asistentes',
        data: attendeesData,
        yAxis: 0
      },
      {
        name: 'Eventos',
        data: eventsData,
        yAxis: 1
      }
    ]
  });
};

// Reset filters
const resetFilters = () => {
  const { start, end } = getDefaultDateRange();
  startDate.value = start;
  endDate.value = end;
  selectedStatus.value = 'Todos';
};

// Lifecycle and watch methods
onMounted(() => {
  debugFilter();
  renderEventChart();
  renderAttendeesChart();
});

watch([
  () => isResp.value,
  () => classroomN.value,
  () => facultadN.value,
  () => filteredReservations.value,
  () => props.reservations_classrooms,
  startDate,
  endDate,
  selectedStatus
], () => {
  renderEventChart();
  renderAttendeesChart();
}, { deep: true });
</script>

<template>
  <AdminAreaLayout>
    <Head title="Estadísticas de Aula" />
    <template #header>
      <h2 class="text-4xl font-semibold leading-tight text-gray-800 dark:text-gray-200 text-center">
        Estadísticas | {{ classroomN.name || 'Aula' }}
      </h2>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6">
            <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Filtros</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label for="startDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha de Inicio</label>
                <input
                  type="date"
                  v-model="startDate"
                  id="startDate"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                />
              </div>
              <div>
                <label for="endDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha de Fin</label>
                <input
                  type="date"
                  v-model="endDate"
                  id="endDate"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                />
                <p v-if="!isValidDateRange" class="text-red-500 text-sm mt-1">La fecha de fin debe ser posterior a la fecha de inicio.</p>
              </div>
              <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                <select
                  v-model="selectedStatus"
                  id="status"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                >
                  <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
                </select>
              </div>
              <div class="flex items-end">
                <button
                  @click="resetFilters"
                  class="w-full px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500 transition"
                >
                  Restablecer Filtros
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Mostrar estadísticas si hay datos válidos -->
        <div v-if="hasValidData">
          <!-- Resumen de estadísticas -->
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
              <h3 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Resumen General</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                  <p class="text-sm text-blue-600 dark:text-blue-300 font-medium">Total de Eventos</p>
                  <p class="text-3xl font-bold text-blue-800 dark:text-blue-100">{{ totalStats.totalEvents }}</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                  <p class="text-sm text-green-600 dark:text-green-300 font-medium">Total de Asistentes</p>
                  <p class="text-3xl font-bold text-green-800 dark:text-green-100">{{ totalStats.totalAttendees }}</p>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg">
                  <p class="text-sm text-purple-600 dark:text-purple-300 font-medium">Promedio de Asistentes por Evento</p>
                  <p class="text-3xl font-bold text-purple-800 dark:text-purple-100">
                    {{ totalStats.totalEvents > 0 ? Math.round(totalStats.totalAttendees / totalStats.totalEvents) : 0 }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Gráficos -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Gráfico de eventos por estado -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
              <div ref="eventChartContainer" class="w-full h-80"></div>
            </div>
            <!-- Gráfico de asistentes por estado -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
              <div ref="attendeesChartContainer" class="w-full h-80"></div>
            </div>
          </div>

          <!-- Tabla detallada de estadísticas -->
          <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Estadísticas Detalladas {{ classroomN.name }}</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Facultad</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aula</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Eventos</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Asistentes</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Promedio por Evento</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                  <tr v-for="(data, status) in totalStats.byStatus" :key="status" 
                      class="hover:bg-gray-50 dark:hover:bg-gray-600"
                      :class="{
                        'bg-green-50 dark:bg-green-900/20': status.toLowerCase() === 'Aprobado',
                        'bg-yellow-50 dark:bg-yellow-900/20': status.toLowerCase() === 'Pendiente',
                        'bg-red-50 dark:bg-red-900/20': status.toLowerCase() === 'Rechazado',
                        'bg-gray-50 dark:bg-gray-900/20': status.toLowerCase() === 'Cancelado',
                        'bg-orange-50 dark:bg-orange-900/20': status.toLowerCase() === 'No_realizado',
                        'bg-blue-50 dark:bg-blue-900/20': status.toLowerCase() === 'Realizado'
                      }">
                    <td class="px-6 py-4 whitespace-nowrap">{{ facultadN.name || '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ classroomN.name || '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap font-medium"
                        :class="{
                          'text-green-600 dark:text-green-400': status.toLowerCase() === 'aprobado',
                          'text-yellow-600 dark:text-yellow-400': status.toLowerCase() === 'pendiente',
                          'text-red-600 dark:text-red-400': status.toLowerCase() === 'rechazado',
                          'text-gray-600 dark:text-gray-400': status.toLowerCase() === 'cancelado',
                          'text-orange-600 dark:text-orange-400': status.toLowerCase() === 'no_realizado',
                          'text-blue-600 dark:text-blue-400': status.toLowerCase() === 'realizado'
                        }">
                      {{ status }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ data.events }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ data.attendees }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ data.events > 0 ? Math.round(data.attendees / data.events) : 0 }}</td>
                  </tr>
                  <!-- Fila de totales -->
                  <tr class="bg-gray-100 dark:bg-gray-700 font-semibold">
                    <td class="px-6 py-4 whitespace-nowrap" colspan="3">Totales</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ totalStats.totalEvents }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ totalStats.totalAttendees }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ totalStats.totalEvents > 0 ? Math.round(totalStats.totalAttendees / totalStats.totalEvents) : 0 }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Mensaje cuando no hay datos -->
        <div v-else class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-center">
          <div class="flex flex-col items-center justify-center py-12">
            <svg class="w-16 h-16 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">No hay datos disponibles</h3>
            <p class="text-gray-500 dark:text-gray-400 max-w-md">
              No se encontraron reservaciones para {{ classroomN.name || 'de esta Aula' }} con los filtros seleccionados.
              Verifica lo siguiente:
              <ul class="list-disc list-inside mt-2">
                <li v-if="!props.reservations_classrooms.length">No hay reservaciones en la base de datos.</li>
                <li>El rango de fechas podría ser demasiado estrecho.</li>
                <li>El filtro de estado podría estar excluyendo todos los eventos.</li>
                <li>El filtro de aula podría no coincidir con ninguna reservación.</li>
              </ul>
            </p>
          </div>
        </div>
      </div>
    </div>
  </AdminAreaLayout>
</template>