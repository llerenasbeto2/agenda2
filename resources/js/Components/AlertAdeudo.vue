<script setup>
import { ref, defineProps, defineEmits } from 'vue';
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Ingresos/Adeudos'
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
            <h4 class="font-bold">CONTACTO</h4>
            <p>Red de Centros Interactivos de Aprendizaje Multimedia</p>
            <p>Dirección General de Recursos Educativos</p>
            <p>Universidad de Colima</p>
            <p><a href="http://ciam.ucol.mx" class="text-blue-600 dark:text-blue-400 hover:underline">http://ciam.ucol.mx</a></p>
            
            <div class="pt-2">
              <p><span class="font-semibold">Correo electrónico:</span> ciam@ucol.mx</p>
            </div>
            
            <div class="pt-2">
              <p class="font-semibold">Teléfono:</p>
              <p>(+52) 312 316 1093</p>
              <p>(+52) 312 316 1000 Exts: 35600, 35601, 35602</p>
              <p>Fax (+52) 312 316 1000 Ext: 35602</p>
            </div>
            
            <div class="pt-2">
              <p class="font-semibold">Dirección:</p>
              <p>Universidad de Colima - Medios Didácticos</p>
              <p>Av. Universidad # 333. Las Víboras.</p>
              <p>Colima, Colima.</p>
              <p>México 28040</p>
              <p>DGRE <a href="http://portal.ucol.mx/dgre/" class="text-blue-600 dark:text-blue-400 hover:underline">http://portal.ucol.mx/dgre/</a></p>
            </div>
          </div>
          
          <!-- Pie del modal -->
          <div class="flex justify-center border-t dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-6 py-3">
            <button 
              @click="closeModal" 
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