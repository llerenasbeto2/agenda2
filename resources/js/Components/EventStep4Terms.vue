<template>
    <div class="bg-gray-50 p-4 rounded-lg">
      <h2 class="text-lg font-semibold mb-4 text-black">Términos y Condiciones</h2>
      <div class="form-group">
        <label for="requirements" class="block font-medium mb-1 text-black">Requerimientos Especiales</label>
        <textarea
          id="requirements"
          v-model="form.requirements"
          rows="4"
          class="w-full px-3 py-2 border rounded-md"
        ></textarea>
        <p v-if="errors.requirements" class="text-red-500 text-sm mt-1">{{ errors.requirements }}</p>
      </div>
    </div>
  </template>
  
  <script setup>
  defineProps({
    form: Object,
    errors: Object
  });
  </script>
  