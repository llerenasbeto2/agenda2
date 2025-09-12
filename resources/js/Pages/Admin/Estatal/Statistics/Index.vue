<script setup>
import AdminEstatalLayout from '@/Layouts/AdminEstatalLayout.vue';
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

// Compute user's responsible faculty
const facultyId = computed(() => page.props.auth.user?.responsible);

// Faculty details
const faculty = computed(() => {
  return props.faculties.find(f => Number(f.id) === Number(facultyId.value)) || {};
});

// Available classrooms for the faculty
const availableClassrooms = computed(() => {
  return props.classrooms.filter(c => Number(c.faculty_id) === Number(faculty.value.id));
});

// Filter controls
const { start, end } = getDefaultDateRange();
const startDate = ref(start);
const endDate = ref(end);
const selectedStatus = ref('Todos');
const selectedClassroom = ref('Todas');
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

// Filtered reservations
const filteredReservations = computed(() => {
  if (!facultyId.value || !faculty.value.id) {
    console.warn('Filtros iniciales no cumplidos:', {
      facultyId: facultyId.value,
      faculty: faculty.value
    });
    return [];
  }

  // Filter reservations by faculty
  let filtered = props.reservations_classrooms.filter(
    res => Number(res.faculty_id) === Number(faculty.value.id)
  );

  // Apply classroom filter
  if (selectedClassroom.value !== 'Todas') {
    filtered = filtered.filter(
      res => Number(res.classroom_id) === Number(selectedClassroom.value)
    );
  }

  // Apply date range filter
  if (startDate.value && endDate.value && isValidDateRange.value) {
    const start = new Date(startDate.value);
    start.setHours(0, 0, 0, 0);
    const end = new Date(endDate.value);
    end.setHours(23, 59, 59, 999);

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
              .filter((date) => !isNaN(date.getTime()));
          }
        } catch (e) {
          console.error(`Error parsing irregular dates for reservation ${res.id}:`, e);
        }
      }

      // Handle single event
      if (!res.is_recurring && !res.irregular_dates && res.start_datetime) {
        const singleDate = new Date(res.start_datetime);
        if (!isNaN(singleDate.getTime())) {
          eventDates.push(singleDate);
        }
      }

      // Handle recurring events
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

      return eventDates.some((date) => date >= start && date <= end);
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

// Calculate repetitions for a reservation
const calculateRepetitions = (reservation) => {
  if (startDate.value && endDate.value && isValidDateRange.value) {
    const start = new Date(startDate.value);
    start.setHours(0, 0, 0, 0);
    const end = new Date(endDate.value);
    end.setHours(23, 59, 59, 999);

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
        console.error(`Error parsing irregular dates for reservation ${reservation.id}:`, e);
        return 0;
      }
    }

    if (!reservation.is_recurring && !reservation.irregular_dates && reservation.start_datetime) {
      const eventDate = new Date(reservation.start_datetime);
      return !isNaN(eventDate.getTime()) && eventDate >= start && eventDate <= end ? 1 : 0;
    }

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
        console.error(`Error calculating recurring events for reservation ${reservation.id}:`, e);
        return 0;
      }
    }
  }

  return reservation.irregular_dates
    ? JSON.parse(reservation.irregular_dates)?.length || 1
    : reservation.is_recurring
    ? Number(reservation.repeticion) || 1
    : 1;
};

// Normalize status case
const normalizeStatus = (status) => {
  if (!status) return '';
  return status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
};

// Calculate statistics by status and classroom
const statsByStatusAndClassroom = computed(() => {
  const stats = {};

  if (filteredReservations.value.length === 0) {
    return stats;
  }

  filteredReservations.value.forEach(res => {
    const normalizedStatus = normalizeStatus(res.status);
    const classroomId = Number(res.classroom_id);

    if (!normalizedStatus || !classroomId) {
      return;
    }

    if (!stats[classroomId]) {
      stats[classroomId] = {
        Aprobado: { count: 0, attendees: 0 },
        Pendiente: { count: 0, attendees: 0 },
        Rechazado: { count: 0, attendees: 0 },
        Cancelado: { count: 0, attendees: 0 },
        No_realizado: { count: 0, attendees: 0 },
        Realizado: { count: 0, attendees: 0 }
      };
    }

    const repetitions = calculateRepetitions(res);
    const attendeesPerEvent = Number(res.attendees) || 0;

    stats[classroomId][normalizedStatus].count += repetitions;
    stats[classroomId][normalizedStatus].attendees += (attendeesPerEvent * repetitions);
  });

  return stats;
});

// Calculate total statistics
const totalStats = computed(() => {
  const totals = {
    totalEvents: 0,
    totalAttendees: 0,
    byStatusAndClassroom: {}
  };

  Object.entries(statsByStatusAndClassroom.value).forEach(([classroomId, statuses]) => {
    totals.byStatusAndClassroom[classroomId] = {};

    Object.entries(statuses).forEach(([status, data]) => {
      if (data.count > 0) {
        totals.totalEvents += data.count;
        totals.totalAttendees += data.attendees;
        totals.byStatusAndClassroom[classroomId][status] = {
          events: data.count,
          attendees: data.attendees
        };
      }
    });
  });

  return totals;
});

// Check if there is valid data to render
const hasValidData = computed(() => {
  return (
    props.classrooms?.length > 0 &&
    props.reservations_classrooms?.length > 0 &&
    props.faculties?.length > 0 &&
    filteredReservations.value.length > 0
  );
});

// Chart containers
const eventChartContainer = ref(null);
const attendeesChartContainer = ref(null);

// Debug function
const debugFilter = () => {
  console.log({
    activeFacultyId: facultyId.value,
    selectedFaculty: faculty.value,
    selectedClassroom: selectedClassroom.value,
    availableClassrooms: availableClassrooms.value,
    filteredReservationsCount: filteredReservations.value.length,
    reservationsRawCount: props.reservations_classrooms.length,
    startDate: startDate.value,
    endDate: endDate.value,
    selectedStatus: selectedStatus.value,
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

// Render event chart
const renderEventChart = () => {
  if (!hasValidData.value || !eventChartContainer.value) return;

  debugFilter();

  const statusTotals = {};
  Object.values(statsByStatusAndClassroom.value).forEach(statuses => {
    Object.entries(statuses).forEach(([status, data]) => {
      if (!data) {
        console.warn(`Skipping undefined data for status ${status}`);
        return;
      }
      if (!statusTotals[status]) {
        statusTotals[status] = { count: 0, attendees: 0 };
      }
      statusTotals[status].count += data.count;
      statusTotals[status].attendees += data.attendees;
    });
  });

  const chartData = Object.entries(statusTotals)
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
      filename: `eventos-por-Facultad-${faculty.value?.name || 'Desconocido'}`,
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
    title: { text: `Eventos de la Facultad - ${faculty.value?.name || 'Desconocido'}` },
    subtitle: {
      text: selectedClassroom.value === 'Todas'
        ? `Todas las aulas (${startDate.value} al ${endDate.value})`
        : `Aula: ${props.classrooms.find(c => Number(c.id) === Number(selectedClassroom.value))?.name || 'Desconocida'} (${startDate.value} al ${endDate.value})`
    },
    tooltip: {
      pointFormat: '<b>{point.name}</b><br>{series.name}: <b>{point.y}</b><br>Porcentaje: <b>{point.percentage:.1f}%</b>'
    },
    plotOptions: {
      series: {
        allowPointSelect: true,
        cursor: 'pointer',
        dataLabels: [{
          enabled: true,
          distance: 20,
          format: '{point.name}'
        }, {
          enabled: true,
          distance: -40,
          format: '{point.percentage:.1f}%',
          style: { fontSize: '1.2em', textOutline: 'none', opacity: 0.7 },
          filter: { operator: '>', property: 'percentage', value: 5 }
        }]
      }
    },
    series: [{
      name: 'Eventos',
      colorByPoint: false, // Disable automatic colors
      data: chartData
    }]
  });
};

// Render attendees chart
const renderAttendeesChart = () => {
  if (!hasValidData.value || !attendeesChartContainer.value) return;

  const statusTotals = {};
  Object.values(statsByStatusAndClassroom.value).forEach(statuses => {
    Object.entries(statuses).forEach(([status, data]) => {
      if (!data) {
        console.warn(`Skipping undefined data for status ${status}`);
        return;
      }
      if (!statusTotals[status]) {
        statusTotals[status] = { count: 0, attendees: 0 };
      }
      statusTotals[status].count += data.count;
      statusTotals[status].attendees += data.attendees;
    });
  });

  const statuses = Object.keys(statusTotals);
  const attendeesData = Object.values(statusTotals).map((data, index) => ({
    y: data.attendees,
    color: statusColors[statuses[index]] || '#000000'
  }));
  const eventsData = Object.values(statusTotals).map((data, index) => ({
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
      filename: `asistentes-y-eventos-de-la-Facultad-${faculty.value?.name || 'Desconocido'}`,
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
    title: { text: `Asistentes y Eventos en la Facultad - ${faculty.value?.name || 'Desconocido'}` },
    subtitle: {
      text: selectedClassroom.value === 'Todas'
        ? `Todas las aulas (${startDate.value} al ${endDate.value})`
        : `Aula: ${props.classrooms.find(c => Number(c.id) === Number(selectedClassroom.value))?.name || 'Desconocida'} (${startDate.value} al ${endDate.value})`
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
  selectedClassroom.value = 'Todas';
};

onMounted(() => {
  debugFilter();
  renderEventChart();
  renderAttendeesChart();
});

watch([
  facultyId,
  faculty,
  filteredReservations,
  () => props.reservations_classrooms,
  startDate,
  endDate,
  selectedStatus,
  selectedClassroom
], () => {
  renderEventChart();
  renderAttendeesChart();
}, { deep: true });
</script>

<template>
  <AdminEstatalLayout>
    <Head title="Estadísticas de Facultad" />
    <template #header>
      <h2 class="text-4xl font-semibold leading-tight text-gray-800 dark:text-gray-200 text-center">
        Estadísticas | {{ faculty.name || 'Facultad' }}
      </h2>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6">
            <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Filtros</h3>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
              <div>
                <label for="classroom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Aula</label>
                <select
                  v-model="selectedClassroom"
                  id="classroom"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                >
                  <option value="Todas">Todas</option>
                  <option v-for="classroom in availableClassrooms" :key="classroom.id" :value="classroom.id">
                    {{ classroom.name }}
                  </option>
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
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
              <div ref="eventChartContainer" class="w-full h-80"></div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
              <div ref="attendeesChartContainer" class="w-full h-80"></div>
            </div>
          </div>

          <!-- Tabla detallada de estadísticas -->
          <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-200">Estadísticas Detalladas {{ faculty.name }}</h3>
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
                  <template v-for="(classroomStats, classroomId) in totalStats.byStatusAndClassroom" :key="classroomId">
                    <tr v-for="(data, status) in classroomStats" :key="status"
                        class="hover:bg-gray-50 dark:hover:bg-gray-600"
                        :class="{
                          'bg-green-50 dark:bg-green-900/20': status.toLowerCase() === 'aprobado',
                          'bg-yellow-50 dark:bg-yellow-900/20': status.toLowerCase() === 'pendiente',
                          'bg-red-50 dark:bg-red-900/20': status.toLowerCase() === 'rechazado',
                          'bg-gray-50 dark:bg-gray-900/20': status.toLowerCase() === 'cancelado',
                          'bg-orange-50 dark:bg-orange-900/20': status.toLowerCase() === 'no_realizado',
                          'bg-blue-50 dark:bg-blue-900/20': status.toLowerCase() === 'realizado'
                        }">
                      <td class="px-6 py-4 whitespace-nowrap">{{ faculty.name || '-' }}</td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        {{ props.classrooms.find(c => Number(c.id) === Number(classroomId))?.name || '-' }}
                      </td>
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
                  </template>
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
              No se encontraron reservaciones para {{ faculty.name || 'esta facultad' }} con los filtros seleccionados.
              Verifica lo siguiente:
              <ul class="list-disc list-inside mt-2">
                <li v-if="!props.reservations_classrooms.length">No hay reservaciones en la base de datos.</li>
                <li v-if="!availableClassrooms.length">No hay aulas asociadas a esta facultad.</li>
                <li>El rango de fechas podría ser demasiado estrecho.</li>
                <li>El filtro de estado podría estar excluyendo todos los eventos.</li>
                <li>El filtro de aula podría no coincidir con ninguna reservación.</li>
              </ul>
            </p>
          </div>
        </div>
      </div>
    </div>
  </AdminEstatalLayout>
</template>