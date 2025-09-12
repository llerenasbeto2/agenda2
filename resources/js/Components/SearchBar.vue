<template>
    <div class="relative w-full sm:w-64">
      <input 
        type="text" 
        v-model="searchQuery" 
        placeholder="Buscar escenarios..." 
        class="w-full px-4 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
      />
      <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, watch, defineEmits, defineProps } from 'vue';
  
  // Define props to accept the modelValue prop
  const props = defineProps({
    modelValue: {
      type: String,
      required: true,
    },
  });
  
  // Define emit for updating the modelValue
  const emit = defineEmits();
  
  // Create a reactive search query that syncs with modelValue
  const searchQuery = ref(props.modelValue);
  
  // Watch for changes to searchQuery and emit updates
  watch(searchQuery, (newQuery) => {
    emit('update:modelValue', newQuery);
  });
  </script>
  