<!-- FilterBar.vue -->
<script setup>
defineProps({
  searchQuery: String,
  selectedStatus: String
});

defineEmits(['update:searchQuery', 'update:selectedStatus', 'newReservation']);
</script>

<template>
  <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex flex-col sm:flex-row gap-4">
      <input 
        type="text" 
        :value="searchQuery" 
        @input="$emit('update:searchQuery', $event.target.value)" 
        placeholder="Buscar..." 
        class="w-full sm:w-64 px-4 py-2 border rounded-lg dark:bg-gray-700" 
      />
      <select 
        :value="selectedStatus" 
        @change="$emit('update:selectedStatus', $event.target.value)" 
        class="px-4 py-2 border rounded-lg dark:bg-gray-700"
      >
        <option>Todos</option>
        <option>Aprobado</option>
        <option>Pendiente</option>
        <option>Rechazado</option>
        <option>Cancelado</option>
        <option value="No_realizado">No realizado</option>
        <option>Realizado</option>
      </select>
    </div>
  </div>
</template>