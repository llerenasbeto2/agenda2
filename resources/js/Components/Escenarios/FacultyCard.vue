<script setup>
defineProps({
    faculty: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['show-classrooms']);
</script>

<template>
    <div class="bg-white dark:bg-gray-700 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
        <div class="h-48 overflow-hidden">
            <img 
                :src="faculty.image || '/images/default-faculty.jpg'" 
                :alt="faculty.name"
                class="w-full h-full object-cover"
            />
        </div>
        
        <div class="p-4">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-2">
                {{ faculty.name }}
            </h3>
            
            <p class="text-gray-600 dark:text-gray-300 mb-1">
                <span class="font-medium">Ubicación:</span> {{ faculty.location }}
            </p>
            
            <p class="text-gray-600 dark:text-gray-300 mb-1">
                <span class="font-medium">Capacidad:</span> {{ faculty.capacity }}
            </p>
            
            <p class="text-gray-600 dark:text-gray-300 mb-4 line-clamp-2">
                {{ faculty.description }}
            </p>
            
            <div class="flex justify-between items-center">
                <span class="text-sm text-teal-600 dark:text-teal-400">
                    {{ faculty.classrooms.length }} aulas disponibles
                </span>
                
                <button 
                    @click="emit('show-classrooms', faculty)"
                    class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition"
                >
                    Ver Aulas
                </button>
            </div>
        </div>
    </div>
</template>