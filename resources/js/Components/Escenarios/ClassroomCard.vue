<script setup>
defineProps({
    classroom: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <div class="bg-white dark:bg-gray-700 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
        <div class="h-48 overflow-hidden">
            <img 
                :src="classroom.image || '/images/default-classroom.jpg'" 
                :alt="classroom.name"
                class="w-full h-full object-cover"
            />
        </div>
        
        <div class="p-4">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-2">
                {{ classroom.name }}
            </h3>
            
            <p class="text-gray-600 dark:text-gray-300 mb-1">
                <span class="font-medium">Facultad:</span> {{ classroom.faculty_name }}
            </p>
            
            <p class="text-gray-600 dark:text-gray-300 mb-1">
                <span class="font-medium">Capacidad:</span> {{ classroom.capacity }}
            </p>
            
            <p class="text-gray-600 dark:text-gray-300 mb-4 line-clamp-2">
                {{ classroom.services }}
            </p>
            
            <button class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                Reservar
            </button>
        </div>
    </div>
</template>