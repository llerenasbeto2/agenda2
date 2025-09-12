<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
      <h2 class="text-xl font-semibold mb-6 text-gray-800 dark:text-gray-200">Información del Evento</h2>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="form-group">
          <label for="eventTitle" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Título del Evento</label>
          <input 
            type="text" 
            id="eventTitle" 
            v-model="form.event_title" 
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
            required
          />
          <div v-if="form.errors.event_title" class="text-red-500 text-sm mt-1">{{ form.errors.event_title }}</div>
        </div>
        
        <div class="form-group">
          <label for="categoryType" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Categoría</label>
          <select 
            id="categoryType" 
            v-model="form.category_type" 
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
            required
          >
            <option value="" disabled>Seleccione una categoría</option>
            <option v-for="category in formData.categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
          <div v-if="form.errors.category_type" class="text-red-500 text-sm mt-1">{{ form.errors.category_type }}</div>
        </div>
        
        <div class="form-group">
          <label for="faculty" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Facultad</label>
          <select 
            id="faculty" 
            v-model="form.faculty_id" 
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
            required
            @change="loadClassrooms"
          >
            <option value="" disabled>Seleccione una facultad</option>
            <option v-for="faculty in formData.faculties" :key="faculty.id" :value="faculty.id">
              {{ faculty.name }}
            </option>
          </select>
          <div v-if="form.errors.faculty_id" class="text-red-500 text-sm mt-1">{{ form.errors.faculty_id }}</div>
        </div>
        
        <div class="form-group">
          <label for="classroom" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Aula</label>
          <select 
            id="classroom" 
            v-model="form.classroom_id" 
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
            required
            :disabled="!form.faculty_id"
          >
            <option value="" disabled>Seleccione un aula</option>
            <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">
              {{ classroom.name }}
            </option>
          </select>
          <div v-if="form.errors.classroom_id" class="text-red-500 text-sm mt-1">{{ form.errors.classroom_id }}</div>
        </div>
        
        <div class="form-group">
          <label for="attendees" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Número de Asistentes</label>
          <input 
            type="number" 
            id="attendees" 
            v-model="form.attendees" 
            min="1" 
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
            required
          />
          <div v-if="form.errors.attendees" class="text-red-500 text-sm mt-1">{{ form.errors.attendees }}</div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue';
  
  const props = defineProps({
    form: Object,
    formData: Object,
    classrooms: Array,
    loadClassrooms: Function
  });
  </script>
  