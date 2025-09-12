<script setup>
import { ref, defineProps, defineEmits } from 'vue';
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Reservación Exitosa'
  }
});
const emit = defineEmits(['close']);
const closeModal = () => {
  emit('close');
};
</script>
<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
      <div 
        class="fixed inset-0 bg-black bg-opacity-50"
        @click="closeModal"
      ></div>
      
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 transform scale-95"
        enter-to-class="opacity-100 transform scale-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 transform scale-100"
        leave-to-class="opacity-0 transform scale-95"
      >
        <div v-if="show" class="mx-4 w-full max-w-md overflow-hidden rounded border bg-white dark:bg-slate-800 shadow-lg z-10">
          <!-- Encabezado del modal -->
          <div class="border-b px-4 py-3 text-center dark:border-slate-600">
            <div class="flex items-center justify-center">
              <div class="mr-2 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                <svg class="h-8 w-8 text-green-600 dark:text-green-300" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
              </div>
              <h3 class="text-lg font-semibold dark:text-white">{{ title }}</h3>
            </div>
          </div>
          
          <!-- Contenido del modal -->
          <div class="space-y-4 p-6 text-center dark:text-white">
            <p class="text-xl font-bold mb-4">¡Su reservación ha sido creada con éxito!</p>
            
            <div class="flex justify-center">
              <div class="w-16 border-b-4 border-green-500 dark:border-green-400 mb-4"></div>
            </div>
            
            <p class="mb-2">
              Su solicitud de reservación ha sido registrada en nuestro sistema y está pendiente de aprobación.
            </p>
            
            <div class="mt-6 bg-green-50 dark:bg-green-900/30 p-4 rounded-lg">
              <p class="font-bold mb-2">Detalles importantes:</p>
              <ul class="text-left space-y-2">
                <li class="flex items-start">
                  <span class="mr-2 text-green-600 dark:text-green-400">•</span>
                  <span>Recibirá una notificación por correo cuando su reservación sea aprobada.</span>
                </li>
                <li class="flex items-start">
                  <span class="mr-2 text-green-600 dark:text-green-400">•</span>
                  <span>Puede consultar el estado de su reservación en el panel de control.</span>
                </li>
                <li class="flex items-start">
                  <span class="mr-2 text-green-600 dark:text-green-400">•</span>
                  <span>Si necesita realizar cambios, contacte al administrador del sistema.</span>
                </li>
              </ul>
            </div>
            
            <p class="mt-4 text-sm italic text-gray-600 dark:text-gray-400">
              Gracias por utilizar nuestro sistema de reservaciones.
            </p>
          </div>
          
          <!-- Pie del modal -->
          <div class="flex justify-center border-t dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-6 py-3">
            <button 
              @click="closeModal" 
              class="rounded bg-green-600 px-4 py-2 font-medium text-white hover:bg-green-700 transition duration-150 hover:shadow-md transform hover:-translate-y-0.5"
            >
              Aceptar
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>