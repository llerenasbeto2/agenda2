<template>
    <div class="flex flex-col md:flex-row">
        <div class="md:w-1/2 bg-teal-100 dark:bg-teal-900 flex flex-col items-center justify-center p-4 gap-4">
            <div class="w-full h-64 md:h-80 rounded-lg flex items-center justify-center overflow-hidden bg-gray-200 dark:bg-gray-800">
                <img 
                    :src="classroom.uses_db_storage ? `data:image/jpeg;base64,${classroom.image_data}` : classroom.image_url" 
                    :alt="classroom.name"
                    class="w-full h-full object-cover"
                    @error="handleImageError"
                />
            </div>
        </div>
        
        <div class="md:w-1/2 p-6">
            <h3 class="text-2xl font-bold text-teal-800 dark:text-teal-300 mb-4">{{ classroom.name }}</h3>
            <p class="text-gray-800 dark:text-gray-200">{{ classroom.location || 'No especificada' }}</p>
            <!-- Más detalles aquí -->
        </div>
    </div>
  </template>
  
  <script setup>
  defineProps({
    classroom: Object
  });
  
  const handleImageError = (event) => {
    event.target.src = '/images/default-classroom.jpg';
  };
  </script>
  