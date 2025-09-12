<script setup>
import { defineProps, defineEmits, ref, watch } from 'vue';
import { computed } from 'vue';

const props = defineProps({
  faculties: { type: Array, required: true },
  municipalityId: { type: [String, Number, null], required: true },
  initialClassrooms: { type: Array, default: () => [] },
  facultyId: { type: [String, Number, null], default: null },
  classroomId: { type: [String, Number, null], default: null },
});

const emit = defineEmits(['update:faculty-id', 'update:classroom-id']);

const selectedFacultyId = ref(props.facultyId);
const selectedClassroomId = ref(props.classroomId);

// Filtrar facultades según municipality_id
const filteredFaculties = computed(() => {
  return props.faculties.filter(faculty => faculty.municipality_id === props.municipalityId);
});

// Obtener la facultad seleccionada
const selectedFaculty = computed(() => {
  const faculty = filteredFaculties.value.find(faculty => faculty.id === selectedFacultyId.value) || null;
  console.log('Selected Faculty:', faculty); // Depuración
  return faculty;
});

// Filtrar salones según faculty_id seleccionado
const filteredClassrooms = computed(() => {
  return props.initialClassrooms.filter(classroom => classroom.faculty_id === selectedFacultyId.value);
});

// Emitir cambios al form padre
watch(selectedFacultyId, (newVal) => {
  emit('update:faculty-id', newVal);
  if (newVal && selectedClassroomId.value) {
    // No resetear el salón si ya estaba seleccionado
    emit('update:classroom-id', selectedClassroomId.value);
  } else if (newVal) {
    selectedClassroomId.value = null;
    emit('update:classroom-id', null);
  }
});
watch(selectedClassroomId, (newVal) => emit('update:classroom-id', newVal));

// Sincronizar props iniciales
watch(() => props.facultyId, (newVal) => {
  selectedFacultyId.value = newVal;
});
watch(() => props.classroomId, (newVal) => {
  selectedClassroomId.value = newVal;
});

// Obtener imagen válida de un salón
const getClassroomImage = (classroom) => {
  return classroom.image_url || classroom.image_path || '/placeholder-image.jpg';
};

// Obtener imagen válida de una facultad
const getFacultyImage = (faculty) => {
  const baseUrl = faculty?.image?.startsWith('http') ? '' : '/storage/';
  return faculty?.image ? `${baseUrl}${faculty.image}` : '/placeholder-image.jpg';
};
</script>

<template>
  <div class="space-y-6">
    <!-- Selección de Facultad y Detalles en una fila -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
      <!-- Selección de Facultad -->
      <div>
        <label class="block text-sm font-medium text-gray-900 dark:text-gray-200">Facultad</label>
        <select
          v-model="selectedFacultyId"
          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 sm:text-sm p-2"
        >
          <option value="">Seleccione una facultad</option>
          <option v-for="faculty in filteredFaculties" :key="faculty.id" :value="faculty.id">
            {{ faculty.name }}
          </option>
        </select>
      </div>

      <!-- Detalles de la Facultad Seleccionada -->
      <div v-if="selectedFaculty" class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-4">
          <img :src="getFacultyImage(selectedFaculty)" alt="Imagen de la Facultad" class="w-24 h-24 object-cover rounded-md" />
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200">{{ selectedFaculty.name }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Responsable: {{ selectedFaculty.responsible?.trim() || 'No especificado' }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">Sitio web: 
              <a v-if="selectedFaculty.web_site?.trim()" :href="selectedFaculty.web_site" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 underline">
                {{ selectedFaculty.web_site }}
              </a>
              <span v-else>No especificado</span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Selección de Salón (si hay facultad seleccionada) -->
    <div v-if="selectedFacultyId">
      <label class="block text-sm font-medium text-gray-900 dark:text-gray-200">Salón</label>
      <div v-if="filteredClassrooms.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mt-2">
        <div
          v-for="classroom in filteredClassrooms"
          :key="classroom.id"
          class="border rounded-lg p-4 hover:shadow-lg transition-shadow duration-200 cursor-pointer "
          :class="{ 'border-indigo-500': selectedClassroomId === classroom.id }"
          @click="selectedClassroomId = classroom.id"
        >
          <img :src="getClassroomImage(classroom)" alt="Salón" class="w-full h-32 object-cover rounded-md mb-2" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-200">{{ classroom.name }}</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">Capacidad: {{ classroom.capacity }}</p><br>
          <p class="text-sm text-gray-600 dark:text-gray-400">Servicios: {{ classroom.services }}</p><br>
          <p class="text-sm text-gray-600 dark:text-gray-400">Responsable: {{ classroom.responsible }}</p>
        </div>
      </div>
      <p v-else class="text-sm text-gray-500 dark:text-gray-400 mt-2">No hay salones disponibles para esta facultad.</p>
    </div>
  </div>
</template>