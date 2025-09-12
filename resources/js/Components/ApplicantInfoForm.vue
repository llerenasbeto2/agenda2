<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
      <h2 class="text-xl font-semibold mb-6 text-gray-800 dark:text-gray-200">Información del Solicitante</h2>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="form-group">
          <label for="fullName" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Nombre Completo</label>
          <input 
            type="text" 
            id="fullName" 
            v-model="form.full_name" 
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
            required
          />
          <div v-if="form.errors.full_name" class="text-red-500 text-sm mt-1">{{ form.errors.full_name }}</div>
        </div>
        
        <div class="form-group">
          <label for="email" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Correo Electrónico</label>
          <input 
            type="email" 
            id="email" 
            v-model="form.email" 
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
            required
          />
          <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
        </div>
        
        <div class="form-group">
          <label for="phone" class="block font-medium mb-2 text-gray-700 dark:text-gray-300">Teléfono</label>
          <input 
            type="tel" 
            id="phone" 
            v-model="form.phone" 
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500"
            required
          />
          <div v-if="form.errors.phone" class="text-red-500 text-sm mt-1">{{ form.errors.phone }}</div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  defineProps({
    form: Object
  });
  </script>
  