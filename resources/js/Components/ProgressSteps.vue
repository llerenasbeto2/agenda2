<template>
    <div class="flex justify-center mt-6 space-x-2">
      <button 
        v-for="(_, index) in classrooms" 
        :key="index" 
        @click="goToSlide(index)"
        :class="[ 
          'w-3 h-3 rounded-full transition-colors',
          index === currentIndex ? 'bg-teal-600 dark:bg-teal-500' : 'bg-gray-300 dark:bg-gray-600 hover:bg-teal-400 dark:hover:bg-teal-700'
        ]"
      ></button>
    </div>
  </template>
  
  <script setup>
  const props = defineProps({
    classrooms: Array,
    currentIndex: Number,
  });
  
  const emit = defineEmits(['goToSlide']);
  </script>
  