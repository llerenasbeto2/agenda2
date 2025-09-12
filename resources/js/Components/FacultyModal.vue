<template>
  <div v-if="isOpen" class="fixed inset-0 flex items-center justify-center z-50">
    <!-- Fondo oscuro -->
    <div class="fixed inset-0 bg-black bg-opacity-50" @click="$emit('close')"></div>

    <!-- Contenido del modal -->
    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-lg mx-4 p-6">
      <!-- Imagen -->
       <br> 
      <div class="mb-4">
       <img
    :src="faculty.image"
    :alt="faculty.name"
    class="w-full h-48 object-cover rounded-lg"
  />
      </div>

      <!-- Título -->
      <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">{{ faculty.name }}</h2>

      <!-- Información -->
      <div class="space-y-3 text-gray-600 dark:text-gray-300">
        <p><strong class="text-gray-700 dark:text-gray-200">Aforo:</strong> {{ faculty.capacity }} personas</p>
        <p><strong class="text-gray-700 dark:text-gray-200">Ubicación:</strong> {{ faculty.location }}</p>
        <p v-if="faculty.responsible_user"><strong class="text-gray-700 dark:text-gray-200">Responsable:</strong> {{ faculty.responsible_user.name }}</p>
        <p><strong class="text-gray-700 dark:text-gray-200">Correo electrónico:</strong> {{ faculty.email }}</p>
        <p><strong class="text-gray-700 dark:text-gray-200">Teléfono:</strong> {{ faculty.phone }}</p>
        <p><strong class="text-gray-700 dark:text-gray-200">Servicios:</strong> {{ faculty.services }}</p>
        <p>
          <strong class="text-gray-700 dark:text-gray-200">Sitio web:</strong>
          <a :href="faculty.website || '#'" class="text-blue-500 dark:text-blue-400 hover:underline">Visitar sitio</a>
        </p>
      </div>

      <!-- Botón de reservación -->
      <div class="mt-6 flex justify-center">
        <a
          href="http://agenda.dgre.mx/agendar-evento"
          target="_blank"
          class="bg-blue-500 dark:bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-600 dark:hover:bg-blue-700 transition-colors"
        >
          Reservar
        </a>
      </div>

      <!-- Botón de cerrar -->
      <button
        @click="$emit('close')"
        class="absolute top-4 right-4 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
      >
        ✕
      </button><br>
    </div>
  </div>
</template>

<script setup>
defineProps({
  isOpen: Boolean,
  faculty: {
    type: Object,
    default: () => ({}),
  },
});

defineEmits(['close']);
</script>

<style scoped>
/* No se necesitan estilos adicionales ya que Tailwind maneja todo */
</style>
