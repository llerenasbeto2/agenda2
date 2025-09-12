<script setup>
import { defineProps } from 'vue';

const props = defineProps({
  currentStep: {
    type: Number,
    required: true
  },
  maxReached: {
    type: Number,
    required: true
  },
  goToStep: {
    type: Function,
    required: true
  }
});
</script>

<template>
  <div class="w-full flex justify-center mb-8">
    <div class="relative w-full max-w-3xl">
      <!-- Progress Bar Background -->
      <div class="h-2 bg-gray-200 dark:bg-gray-600 rounded-full"></div>
      <!-- Progress Fill -->
      <div class="h-2 bg-gradient-to-r from-teal-400 to-purple-500 rounded-full absolute top-0 transition-all duration-500" :style="{ width: `${(props.currentStep - 1) * 25}%` }"></div>
      <!-- Step Markers -->
      <div class="flex justify-between -mt-5">
        <div v-for="step in 4" :key="step" class="flex flex-col items-center">
          <button
            @click="props.goToStep(step)"
            class="flex items-center justify-center w-12 h-12 rounded-full text-white font-semibold transition-all duration-300 transform hover:scale-110"
            :class="[
              step <= props.maxReached
                ? 'bg-gradient-to-r from-teal-400 to-purple-500'
                : 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 border-2 border-gray-400 dark:border-gray-500',
              step === props.currentStep ? 'ring-4 ring-teal-300 animate-pulse' : ''
            ]"
            :disabled="step > props.maxReached"
          >
            <span v-if="step < props.currentStep" class="w-6 h-6">
              <svg class="w-full h-full" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
              </svg>
            </span>
            <span v-else-if="step === props.currentStep" class="text-xl">{{ step }}</span>
            <span v-else class="text-xl">{{ step }}</span>
          </button>
          <span v-if="step === props.currentStep" class="mt-2 text-sm text-teal-600 dark:text-teal-400 font-medium">Paso Actual</span>
          <span v-else-if="step < props.currentStep" class="mt-2 text-sm text-gray-500 dark:text-gray-400">Completado</span>
        </div>
      </div>
    </div>
  </div>
</template>