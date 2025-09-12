
<script setup>
import { ref, defineProps, defineEmits } from 'vue';
import Scrollbar from '@/Components/Scrollbar.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Condiciones'
  }
});
const emit = defineEmits(['close']);
const closeModalcondiciones = () => {
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
          @click="closeModalcondiciones"
        ></div>
        
        <Transition
          enter-active-class="transition duration-300 ease-out"
          enter-from-class="opacity-0 transform scale-95"
          enter-to-class="opacity-100 transform scale-100"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="opacity-100 transform scale-100"
          leave-to-class="opacity-0 transform scale-95"
        >
          <div v-if="show" class="mx-4 w-full max-w-2xl overflow-hidden rounded border bg-white dark:bg-slate-800 shadow-lg z-10">
            <!-- Encabezado del modal -->
            <div class="border-b px-4 py-3 text-center dark:border-slate-600">
              <div class="flex items-center justify-center">
                <div class="mr-2 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                  <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <h3 class="text-lg font-semibold dark:text-white uppercase">{{ title }}</h3>
              </div>
            </div>
            <Scrollbar>
            <!-- Contenido del modal -->
            <div class="p-6 text-left dark:text-white">
              <p class="font-bold text-center mb-4">Objetivo</p>
              <p class="mb-3">
                Informar a la comunidad universitaria y a los usuarios de los escenarios educativos de la Universidad de Colima, sobre las reglas y buenas prácticas para el uso de estos espacios con el fin de contribuir a su utilización.
              </p>
              
              <p class="mb-3">
                El ingreso y uso de los escenarios educativos implica el conocimiento, aceptación y compromiso de cumplir con las reglas, normas de comportamiento y demás ordenamientos que los rigen.
              </p>
              
              <p class="mb-3">
                El solicitante del evento se hace responsable del buen uso del equipo y mobiliario dentro de los escenarios educativos. Cualquier mal uso o daño o pérdida causará una sanción económica.
              </p>
              
              <p class="mb-3">
                El equipo instalado en los escenarios educativos no debe moverse por ningún motivo, el responsable de la sala o su auxiliar, por las medidas de seguridad serán los únicos Aprobados para operar los equipos.
              </p>
              
              <p class="mb-3">
                El auxiliar del nivel medio y superior es responsable de la aplicación de la sanción en caso de que ocurra un daño así como del estudiante en forma solidaria.
              </p>
              
              <p class="mb-3">
                La introducción de alimentos y bebidas a los escenarios educativos, en caso fumar dentro de ellos se debe respetar estrictamente los horarios definidos para el servicio solicitado.
              </p>
              
              <p class="mb-3">
                Para la sala dentro los usuarios de servicio didáctico, deberán consultar su horario por internet, confirmar y asistir puntualmente a sus clases.
              </p>
              
              <p class="mb-3">
                El responsable del escenario educativo tiene la autoridad para solicitar que un usuario se retire del lugar, si no cumple con las disposiciones anteriores.
              </p>
              
              <p class="mb-3">
                En caso de utilizar sonido con volumen alto de más de un escenario se tendrá que llegar a un acuerdo estableciendo previamente el desarrollo de su festín y presentar la lista de equipo correspondiente. En caso de cancelación o transferencia de reservas se realizará la solicitud al personal responsable.
              </p>
              
              <p class="mb-3">
                Para tener acceso a los escenarios un usuario deberá solicitar el escenario al menos con tres días hábiles de anticipación.
              </p>
              
              <p class="mb-3">
                Se establece un tiempo máximo de tolerancia para el inicio de clases de 15 minutos para cursos o talleres, de lo contrario se dará por cancelado el evento.
              </p>
              
              <p class="mb-3">
                Si un estudiante no asiste en 3 eventos educativos consecutivos, se le negará el acceso por faltas injustificadas.
              </p>
              
              <p class="mb-3">
                Si tiene alguna duda favor de escribir a la cuenta dgre@ucol.mx
              </p>
              
              <p class="font-bold text-center mt-4 mb-0">
                Dirección Escenarios Educativos
              </p>
              <p class="text-center mb-0">
                Coordinación General de Servicios Educativos
              </p>
            </div>
             </Scrollbar>
            <!-- Pie del modal -->
            <div class="flex justify-center border-t dark:border-slate-600 bg-gray-50 dark:bg-slate-700 px-6 py-3">
              <button 
                @click="closeModalcondiciones" 
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