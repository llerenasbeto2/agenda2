<script setup>
defineProps({
  status: {
    type: String,
    required: true
  }
});

const statusClasses = {
  'Aprobado': 'bg-green-100 text-green-800',
  'Pendiente': 'bg-yellow-100 text-yellow-800',
  'Rechazado': 'bg-red-100 text-red-800',
  'Cancelado': 'bg-gray-100 text-gray-800',
  'No_realizado': 'bg-orange-100 text-orange-800',
  'Realizado': 'bg-blue-100 text-blue-800'
};
</script>

<template>
  <span 
    :class="[statusClasses[status], 'px-2 py-1 rounded-full text-xs font-medium']"
  >
    {{ status === 'No_realizado' ? 'No realizado' : status }}
  </span>
</template>