<script setup>
import StatusBadge from './StatusBadge.vue';

defineProps({
  reservation: {
    type: Object,
    required: true
  }
});
</script>

<template>
  <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
    <td class="px-6 py-4 whitespace-nowrap">
      <div class="flex items-center">
        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
          #{{ reservation.id }}
        </div>
      </div>
    </td>
    
    <td class="px-6 py-4">
      <div class="text-sm text-gray-900 dark:text-gray-100 font-medium">
        {{ reservation.event_title || 'Sin título' }}
      </div>
      <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        {{ new Date(reservation.start_datetime).toLocaleDateString('es-ES', { 
          year: 'numeric', 
          month: 'short', 
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        }) }}
      </div>
    </td>
    
    <td class="px-6 py-4">
      <div class="text-sm text-gray-900 dark:text-gray-100">
        {{ reservation.full_name || reservation.Email || 'Sin especificar' }}
      </div>
      <div v-if="reservation.Email && reservation.full_name" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        {{ reservation.Email }}
      </div>
    </td>
    
    <td class="px-6 py-4">
      <div class="flex items-center">
        <div class="flex-shrink-0 w-2 h-2 bg-teal-500 rounded-full mr-2"></div>
        <div class="text-sm text-gray-900 dark:text-gray-100">
          {{ reservation.classroom?.name || `Aula ${reservation.classroom_id}` }}
        </div>
      </div>
      <div v-if="reservation.classroom?.building" class="text-sm text-gray-500 dark:text-gray-400 mt-1 ml-4">
        {{ reservation.classroom.building }}
      </div>
    </td>
    
    <td class="px-6 py-4 whitespace-nowrap">
      <StatusBadge :status="reservation.status" />
    </td>
  </tr>
</template>