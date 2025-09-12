<script setup>
import { ref, defineProps, defineEmits } from 'vue';
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Cuenta'
  }
});
const emit = defineEmits(['close']);
const closeModalcuenta = () => {
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
        @click="closeModalcuenta"
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
              <div class="mr-2 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
              </div>
              <h3 class="text-lg font-semibold dark:text-white">{{ title }}</h3>
            </div>
          </div>
          
          <!-- Contenido del modal -->
          <div class="space-y-4 p-6 text-center dark:text-white">
            <p class="mb-2"><span class="font-bold">Nombre del beneficiario:</span> Centro de Investigación Ambiental y Marino A.C. (CIAM)</p>
            <p class="mb-2"><span class="font-bold">Número de cuenta:</span> 987654321098765</p>
            <p class="mb-2"><span class="font-bold">Banco:</span> Banco Solidario Universal</p>
            <p class="mb-2"><span class="font-bold">Clave interbancaria (CLABE):</span> 002233445566778899</p>
            <p class="mb-2"><span class="font-bold">RFC:</span> CIAMR1010WXYZ</p>
            <p class="mb-2"><span class="font-bold">Sucursal:</span> 5678</p>
            <p class="mb-2"><span class="font-bold">Tipo de cuenta:</span> Cuenta corriente</p>
            
            <div class="mt-6">
              <p class="font-bold">Instrucciones adicionales:</p>
              <p class="mt-2">
                Por favor, enviar el comprobante de depósito al correo: 
                <a href="mailto:donativos@ciam.org.mx" class="text-blue-600 dark:text-blue-400 hover:underline">donativos@ciam.org.mx</a>
              </p>
              <p class="mt-2">
                En el asunto, incluir el nombre del proyecto o causa que desea apoyar
              </p>
              <p class="mt-2">
                Para dudas o aclaraciones, comunicarse al teléfono: 
                <a href="tel:01-800-123-4567" class="text-blue-600 dark:text-blue-400 hover:underline">01-800-123-4567</a>
              </p>
              <p class="mt-4">
                Gracias por tu contribución para apoyar nuestras iniciativas
              </p>
            </div>
          </div>
          
          <!-- Pie del modal -->
          <div class="flex justify-center border-t dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-6 py-3">
            <button 
              @click="closeModalcuenta" 
              class="rounded bg-gray-200 dark:bg-slate-600 px-4 py-2 font-medium text-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-slate-500 transition duration-150 hover:shadow-md transform hover:-translate-y-0.5"
            >
              Cerrar
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>