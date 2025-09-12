<template>
    <div class="flex justify-center mt-6 space-x-2">
        <button 
            v-for="(_, index) in filteredClassrooms" 
            :key="index" 
            @click="goToSlide(index)"
            :class="[
                'w-3 h-3 rounded-full transition-colors',
                index === currentIndex ? 'bg-teal-600 dark:bg-teal-500' : 'bg-gray-300 dark:bg-gray-600'
            ]"
        />
    </div>
</template>

<script setup>
defineProps({
    filteredClassrooms: Array,
    currentIndex: Number
});

const goToSlide = (index) => {
    // Emitir evento para ir al slide indicado
};
</script>
