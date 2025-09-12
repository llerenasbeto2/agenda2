<template>
  <div class="filters-panel">
    <div class="filters-row">
      <!-- Filtro de Municipio -->
      <div class="filter-item">
        <select 
          :value="selectedMunicipality" 
          @change="onMunicipalityChange"
          class="filter-select"
        >
          <option value="" class="bg-gray-800 text-white">Municipio</option>
          <option 
            v-for="municipality in municipalities" 
            :key="municipality.id" 
            :value="municipality.id" 
            class="bg-gray-800 text-white"
          >
            {{ municipality.name }}
          </option>
        </select>
      </div>

      <!-- Filtro de Facultad -->
      <div class="filter-item">
        <select 
          :value="selectedFaculty" 
          @change="onFacultyChange"
          :disabled="!selectedMunicipality || isFacultySelectDisabled"
          class="filter-select"
          :class="{ 'disabled-select': !selectedMunicipality || isFacultySelectDisabled }"
        >
          <option value="" class="bg-gray-800 text-white">
            {{ !selectedMunicipality ? 'Selecciona un municipio' : 'Facultad' }}
          </option>
          <option 
            v-for="faculty in filteredFaculties" 
            :key="faculty.id" 
            :value="faculty.id" 
            class="bg-gray-800 text-white"
          >
            {{ faculty.name }}
          </option>
        </select>
      </div>

      <!-- Filtro de Aula -->
      <div class="filter-item">
        <select 
          :value="selectedClassroom" 
          @change="onClassroomChange"
          :disabled="!selectedFaculty || isClassroomSelectDisabled"
          class="filter-select"
          :class="{ 'disabled-select': !selectedFaculty || isClassroomSelectDisabled }"
        >
          <option value="" class="bg-gray-800 text-white">
            {{ !selectedFaculty ? 'Selecciona una facultad' : 'Aula' }}
          </option>
          <option 
            v-for="classroom in filteredClassrooms" 
            :key="classroom.id" 
            :value="classroom.id" 
            class="bg-gray-800 text-white"
          >
            {{ classroom.name }}
          </option>
        </select>
      </div>

      <!-- Botón de Reset -->
      <button 
        @click="resetAllFilters" 
        class="reset-button"
        :disabled="!hasAnyFilter"
        :class="{ 'disabled-button': !hasAnyFilter }"
      >
        Limpiar
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, defineEmits } from 'vue';

const props = defineProps({
  municipalities: {
    type: Array,
    default: () => []
  },
  faculties: {
    type: Array,
    default: () => []
  },
  classrooms: {
    type: Array,
    default: () => []
  },
  selectedMunicipality: {
    type: [String, Number],
    default: ''
  },
  selectedFaculty: {
    type: [String, Number],
    default: ''
  },
  selectedClassroom: {
    type: [String, Number],
    default: ''
  }
});

const emit = defineEmits([
  'update:selectedMunicipality',
  'update:selectedFaculty', 
  'update:selectedClassroom',
  'reset-filters'
]);

// Computed para verificar si hay algún filtro activo
const hasAnyFilter = computed(() => {
  return props.selectedMunicipality || props.selectedFaculty || props.selectedClassroom;
});

// Computed para verificar si el select de facultad debe estar deshabilitado
const isFacultySelectDisabled = computed(() => {
  return !props.selectedMunicipality || filteredFaculties.value.length === 0;
});

// Computed para verificar si el select de aula debe estar deshabilitado
const isClassroomSelectDisabled = computed(() => {
  return !props.selectedFaculty || filteredClassrooms.value.length === 0;
});

// Computed para facultades filtradas por municipio
const filteredFaculties = computed(() => {
  if (!props.faculties?.length) return [];
  
  // Solo mostrar facultades si hay un municipio seleccionado
  if (!props.selectedMunicipality) return [];
  
  return props.faculties.filter(faculty => 
    Number(faculty.municipality_id) === Number(props.selectedMunicipality)
  );
});

// Computed para aulas filtradas por facultad
const filteredClassrooms = computed(() => {
  if (!props.classrooms?.length) return [];
  
  // Solo mostrar aulas si hay una facultad seleccionada
  if (!props.selectedFaculty) return [];
  
  return props.classrooms.filter(classroom => 
    Number(classroom.faculty_id) === Number(props.selectedFaculty)
  );
});

// Event handlers
const onMunicipalityChange = (event) => {
  const value = event.target.value;
  emit('update:selectedMunicipality', value);
  
  // Siempre resetear los filtros dependientes cuando cambia el municipio
  emit('update:selectedFaculty', '');
  emit('update:selectedClassroom', '');
};

const onFacultyChange = (event) => {
  const value = event.target.value;
  emit('update:selectedFaculty', value);
  
  // Resetear aula cuando cambia la facultad
  emit('update:selectedClassroom', '');
};

const onClassroomChange = (event) => {
  const value = event.target.value;
  emit('update:selectedClassroom', value);
};

const resetAllFilters = () => {
  emit('update:selectedMunicipality', '');
  emit('update:selectedFaculty', '');
  emit('update:selectedClassroom', '');
  emit('reset-filters');
};
</script>

<style scoped>
.filters-panel {
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(8px);
  border-radius: 20px;
  padding: 20px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
}

.filters-row {
  display: flex;
  gap: 5px;
  justify-content: center;
  align-items: center;
}

.filter-item {
  position: relative;
}

.filter-select {
  appearance: none;
  padding: 5px 25px 5px 10px;
  background: rgba(255, 255, 255, 0.1) url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="white"><path d="M2 5l4 4 4-4H2z"/></svg>') no-repeat right 10px center;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 10px;
  color: white;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  min-width: 180px;
}

.filter-select:hover:not(:disabled) {
  background-color: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.4);
}

.filter-select:focus {
  outline: none;
  border-color: rgba(255, 255, 255, 0.6);
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1);
}

/* Estilos para selects deshabilitados */
.disabled-select {
  background-color: rgba(255, 255, 255, 0.05) !important;
  color: rgba(255, 255, 255, 0.4) !important;
  cursor: not-allowed !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

.disabled-select:hover {
  background-color: rgba(255, 255, 255, 0.05) !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

.reset-button {
  padding: 5px 15px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 10px;
  color: white;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  white-space: nowrap;
}

.reset-button:hover:not(:disabled) {
  background-color: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.4);
}

/* Estilos para botón deshabilitado */
.disabled-button {
  background-color: rgba(255, 255, 255, 0.05) !important;
  color: rgba(255, 255, 255, 0.4) !important;
  cursor: not-allowed !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

.disabled-button:hover {
  background-color: rgba(255, 255, 255, 0.05) !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

/* Responsive para pantallas pequeñas */
@media (max-width: 1024px) {
  .filters-row {
    justify-content: center;
    gap: 6px;
  }
  
  .filter-select {
    width: 160px;
    font-size: 13px;
  }
  
  .reset-button {
    font-size: 13px;
    padding: 5px 12px;
  }
}

@media (max-width: 768px) {
  .filters-row {
    flex-direction: column;
    gap: 10px;
  }
  
  .filter-select {
    width: 100%;
    max-width: 280px;
  }
  
  .reset-button {
    width: 100%;
    max-width: 280px;
  }
}
</style>