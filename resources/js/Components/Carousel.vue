<template>
    <div v-if="filteredClassrooms.length > 0" class="relative">
        <div class="flex justify-between absolute top-1/2 left-0 right-0 z-10 transform -translate-y-1/2">
            <button
                @click="$emit('prev-slide')"
                class="bg-teal-600/80 hover:bg-teal-700 text-white rounded-r-lg p-2 focus:outline-none focus:ring-2 focus:ring-teal-500"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            <button
                @click="$emit('next-slide')"
                class="bg-teal-600/80 hover:bg-teal-700 text-white rounded-l-lg p-2 focus:outline-none focus:ring-2 focus:ring-teal-500"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
        <div v-if="currentClassroom" class="fade-in overflow-hidden rounded-lg bg-teal-50 dark:bg-gray-700 shadow-xl mx-auto max-w-4xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-6">
                <!-- Faculty Image and Website -->
                <div>
                    <p class="text-gray-600 dark:text-gray-400 mb-2 font-bold">Imagen de la facultad:</p>
                    <img
                        v-if="currentClassroom.faculty_image && currentClassroom.faculty_image.trim() !== ''"
                        :key="`faculty-${currentClassroom.faculty_id}`"
                        :src="currentClassroom.faculty_image"
                        alt="Faculty Image"
                        class="w-full h-64 object-cover rounded-lg"
                        @error="handleImageError('faculty')"
                    />
                    <p v-else class="text-gray-600 dark:text-gray-400 mt-4">No hay imagen de facultad disponible</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">
                        <strong>Link de la facultad para más detalles:</strong>
                        <a
                            v-if="currentClassroom.web_site && currentClassroom.web_site.trim() !== ''"
                            :href="currentClassroom.web_site"
                            target="_blank"
                            class="text-teal-600 hover:underline dark:text-teal-400"
                        >
                            Link
                        </a>
                        <span v-else>No disponible</span>
                    </p>
                </div>
                <!-- Classroom Details and Image -->
                <div>
                    <h3 class="text-2xl font-bold text-teal-700 dark:text-teal-300">{{ currentClassroom.name }}</h3>
                    <p class="text-gray-600 dark:text-gray-400"><strong>Facultad:</strong> {{ currentClassroom.faculty_name }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong>Ubicación:</strong> {{ currentClassroom.faculty_location }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong>Capacidad:</strong> {{ currentClassroom.capacity }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong>Servicios:</strong> {{ currentClassroom.services }}</p>
                   <p class="text-gray-600 dark:text-gray-400"><strong>Responsable:</strong> {{ currentClassroom.responsible }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong>Email:</strong> {{ currentClassroom.email || 'No disponible' }}</p>
                    <p class="text-gray-600 dark:text-gray-400"><strong>Teléfono:</strong> {{ currentClassroom.phone || 'No disponible' }}</p>
                    <p class="text-gray-600 dark:text-gray-400 mt-2 font-bold">Imagen del aula:</p>
                    <img
                        v-if="currentClassroom.image && currentClassroom.image.trim() !== ''"
                        :key="`classroom-${currentClassroom.id}`"
                        :src="currentClassroom.image"
                        alt="Classroom Image"
                        class="w-full h-32 object-cover rounded-lg mt-2"
                        @error="handleImageError('classroom')"
                    />
                    <p v-else class="text-gray-600 dark:text-gray-400 mt-4">No hay imagen del aula disponible</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    filteredClassrooms: {
        type: Array,
        required: true,
    },
    currentIndex: {
        type: Number,
        required: true,
    },
});

const currentClassroom = computed(() => {
    const classroom = props.filteredClassrooms[props.currentIndex] || null;
    console.log('Carousel currentClassroom:', JSON.stringify(classroom, null, 2));
    return classroom;
});

const handleImageError = (type) => {
    console.error(`Failed to load ${type} image for classroom ID: ${currentClassroom.value?.id}`);
};
</script>
