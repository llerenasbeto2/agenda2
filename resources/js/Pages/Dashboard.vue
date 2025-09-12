<template>
  <Head title="Dashboard" />

  <DynamicLayout>
    <div class="relative min-h-screen bg-space">
      <div class="container mx-auto py-16 px-6">
        <div class="text-center text-white mb-12">
          <h1 class="text-4xl font-extrabold mb-4">Escenarios Académicos</h1>
          <p class="text-lg text-gray-300">Gestiona y explora los escenarios de la Universidad de Colima.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          
          <!-- Unidades -->
          <section class="lg:col-span-2 units-grid">
            <UnitCard
              v-for="unit in units"
              :key="unit.name"
              :name="unit.name"
              :image="unit.image"
            />
          </section>

          <!-- Calendario - PROP CORREGIDA -->
          <EventCalendar 
            :events="events" 
            :faculties="faculties" 
            :municipality="municipalities"  
            :classrooms="classrooms"  
          />
        </div>

      </div>
    </div>
  </DynamicLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import DynamicLayout from '@/Layouts/DynamicLayout.vue';
import UnitCard from '@/Components/FacultyModal.vue';
import EventCalendar from '@/Components/Dashboard/EventCalendar.vue';
import axios from 'axios';

const units = [
  { name: "DGRE", image: "https://dgre2.ucol.mx/agenda/app/views/images/escenarios/foto_esc1803201611215642.jpg" },
  { name: "Facultad de Telemática", image: "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS7t9u4qYdjX0TWBywoFR2Qu3qRtYM9Ts1o1A&s" },
  { name: "Facultad de Psicología", image: "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTv-wHtgyCVHdeyJpkvFUGjngRHSGwzr2QPLg&s" },
];

const events = ref([]);
const municipalities = ref([]);
const faculties = ref([]);
const classrooms = ref([]);

onMounted(async () => {
  try {
    console.log('🚀 Iniciando carga de datos...');
    
    // Cargar municipios PRIMERO
    console.log('📍 Cargando municipios...');
    const municipalitiesResponse = await axios.get('/api/municipalities');
    municipalities.value = municipalitiesResponse.data;
    console.log('✅ Municipios cargados:', municipalities.value.length, municipalities.value);

    // Cargar facultades
    console.log('🏛️ Cargando facultades...');
    const facultiesResponse = await axios.get('/api/faculties');
    faculties.value = facultiesResponse.data;
    console.log('✅ Facultades cargadas:', faculties.value.length, faculties.value);

    // Cargar aulas/classrooms
    console.log('🏫 Cargando aulas...');
    const classroomsResponse = await axios.get('/api/classrooms');
    classrooms.value = classroomsResponse.data;
    console.log('✅ Aulas cargadas:', classrooms.value.length, classrooms.value);

    // Cargar reservaciones aprobadas
    console.log('📅 Cargando eventos...');
    const response = await axios.get('/api/approved-reservations');
    console.log('✅ Reservaciones cargadas:', response.data.length, response.data);
    
    // Map the response data to the format expected by EventCalendar
    events.value = response.data.map(event => ({
      title: event.event_title,
      date: event.start_datetime.split('T')[0], // Extract date part only
      start_time: event.start_datetime.split('T')[1]?.substring(0, 5) || '00:00',
      end_time: event.end_datetime?.split('T')[1]?.substring(0, 5) || '23:59',
      classroom: event.classroom?.name || event.classroom_name || 'No especificado',
      faculty: event.faculty?.name || event.faculty_name || 'No especificado',
      faculty_id: event.faculty_id || event.faculty?.id,
      classroom_id: event.classroom_id || event.classroom?.id
    }));
    
    console.log('🎯 Eventos mapeados:', events.value.length);
    console.log('🔍 Ejemplo de evento:', events.value[0]);
    
    console.log('✨ Todos los datos cargados correctamente');
    
  } catch (error) {
    console.error('❌ Error fetching data:', error);
    console.error('Error details:', error.response?.data);
  }
});
</script>

<style scoped>
.bg-space {
  background: url('https://images.unsplash.com/photo-1572216744811-507c5c78e5d5?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') no-repeat center center fixed;
  background-size: cover;
}

.units-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 30px;
}
</style>