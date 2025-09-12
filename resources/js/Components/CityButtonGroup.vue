<template>
    <div class="flex justify-center my-5">
      <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-3 transition-colors">
        <div class="flex flex-wrap gap-2 justify-center">
          <button 
            v-for="(city, index) in cities" 
            :key="index"
            :class="[
              'px-5 py-2 text-sm font-semibold rounded-lg transition-colors duration-300',
              selectedCity === city.id 
                ? 'bg-blue-600 text-white dark:bg-blue-500 dark:text-gray-100 shadow-md' 
                : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
            ]"
            @click="selectCity(city)"
          >
            {{ city.name }}
          </button>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    name: 'CityButtonGroup',
    data() {
      return {
        cities: [
          { id: 1, name: 'Colima' },
          { id: 2, name: 'V de A' },
          { id: 3, name: 'Coquimatlán' },
          { id: 4, name: 'Tecomán' },
          { id: 5, name: 'Manzanillo' },
        ],
        selectedCity: 1 // ID del municipio seleccionado por defecto
      };
    },
    methods: {
      selectCity(city) {
        this.selectedCity = city.id;
        this.$emit('city-selected', city.id);
      }
    }
  };
  </script>
  
  <style scoped>
  /* Asegura una transición fluida en el cambio de temas */
  button {
    transition: background-color 0.3s ease, color 0.3s ease;
  }
  </style>
