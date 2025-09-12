<template>
    <div class="bg-gray-50 p-4 rounded-lg">
      <h2 class="text-lg font-semibold mb-4 text-black">Información del Solicitante</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="form-group">
          <label for="fullName" class="block font-medium mb-1 text-black">Nombre Completo</label>
          <input
            id="fullName"
            type="text"
            v-model="form.full_name"
            class="w-full px-3 py-2 border rounded-md"
            required
          />
          <p v-if="errors.full_name" class="text-red-500 text-sm mt-1">{{ errors.full_name }}</p>
        </div>
        <div class="form-group">
          <label for="email" class="block font-medium mb-1 text-black">Correo Electrónico</label>
          <input
            id="email"
            type="email"
            v-model="form.Email"
            class="w-full px-3 py-2 border rounded-md"
            required
          />
          <p v-if="errors.Email" class="text-red-500 text-sm mt-1">{{ errors.Email }}</p>
        </div>
        <div class="form-group">
          <label for="phone" class="block font-medium mb-1 text-black">Teléfono</label>
          <input
            id="phone"
            type="tel"
            v-model="form.phone"
            class="w-full px-3 py-2 border rounded-md"
            required
          />
          <p v-if="errors.phone" class="text-red-500 text-sm mt-1">{{ errors.phone }}</p>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  defineProps({
    form: Object,
    errors: Object
  });
  </script>
  