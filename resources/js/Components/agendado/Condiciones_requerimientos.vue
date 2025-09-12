<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-2xl" @click.stop>
      <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-200 mb-4">Condiciones de Uso de los Escenarios Educativos</h2>
      
      <div class="text-sm text-gray-700 dark:text-gray-300 space-y-4 overflow-y-auto max-h-[70vh]">
        <p><strong>Dirección de Escenarios Educativos</strong></p>
        
        <h3 class="font-medium">Objetivo</h3>
        <p>Informar a la comunidad universitaria y a los usuarios de los escenarios educativos de la Universidad de Colima las normas que rigen el uso de estos escenarios y a las cuales se sujetan durante su utilización.</p>
        
        <p>El ingreso y uso de los escenarios educativos implica el conocimiento, aceptación y compromiso de vigilancia de estas condiciones de uso de los escenarios educativos por parte de los usuarios.</p>
        
        <p>El solicitante del evento se hace responsable del buen uso del equipo y mobiliario dentro de los escenarios educativos. Cualquier mal uso, daño o pérdida causará una sanción y estará obligado a devolver el monto o reponer el objeto en cuestión.</p>
        
        <p>El equipo instalado en los escenarios educativos debe ser operado por responsable de la sala o, en su caso, por los usuarios siguiendo las instrucciones proporcionadas por el responsable.</p>
        
        <p>El cuidado del buen orden y conducta del grupo de personas es responsabilidad de cada usuario así como del solicitante en forma solidaria.</p>
        
        <p>La introducción de comidas y/o bebidas a los escenarios educativos, así como fumar dentro de ellos están estrictamente prohibidos.</p>
        
        <p>Se deben respetar estrictamente los horarios definidos para el servicio solicitado.</p>
        
        <p>Para su uso óptimo, los usuarios del servicio deberán ajustarse a las instrucciones y recomendaciones del personal.</p>
        
        <p>El responsable del escenario educativo tiene la autoridad para señalar que un usuario se retire del lugar, en caso de no cumplir con los lineamientos de este reglamento.</p>
        
        <p>En su caso (ver apartado Tarifas), el solicitante de la sala deberá cubrir la cuota de recuperación establecida previamente al desarrollo de su sesión y presentar la ficha de depósito correspondiente.</p>
        
        <p>En caso de cancelación o interrupción del servicio se reembolsará al solicitante la parte proporcional del servicio no prestado.</p>
        
        <p>Para tener acceso a los escenarios un usuario deberá solicitar el escenario al menos con tres días hábiles de anticipación.</p>
        
        <p>Se establece un tiempo máximo de espera de 15 minutos para el inicio de clases y de 25 minutos para cursos o talleres, de lo contrario se dará por cancelado el evento.</p>
        
        <p>Si un solicitante incurre en 3 eventos cancelados de manera consecutiva, se le negará el servicio por los seis meses siguientes.</p>
        
        <p>Si tiene alguna queja favor de escribir a la cuenta <a href="mailto:dgre@ucol.mx" class="text-indigo-600 hover:underline">dgre@ucol.mx</a>.</p>
        
        <p><strong>Atentamente,</strong></p>
        <p>Dirección Escenarios Educativos</p>
        <p>Dirección General de Recursos Educativos</p>
        <p>Universidad de Colima</p>
      </div>
      
      <div class="mt-6 flex justify-end">
        <button
          @click="$emit('close')"
          class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          Cerrar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineEmits(['close']);
</script>