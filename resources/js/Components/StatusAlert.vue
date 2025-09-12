<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: 'Confirmar acción'
    },
    message: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['confirm', 'cancel']);

const handleConfirm = () => {
    emit('confirm');
};

const handleCancel = () => {
    emit('cancel');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
        <!-- Overlay/Fondo oscuro -->
        <div class="absolute inset-0 bg-black opacity-50"></div>
        
        <!-- Contenido de la alerta -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-sm mx-auto z-10 shadow-xl">
            <div class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">
                {{ title }}
            </div>
            
            <div class="mb-6 text-gray-600 dark:text-gray-300">
                {{ message }}
            </div>
            
            <div class="flex justify-end space-x-3">
                <button @click="handleCancel" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded">
                    Cancelar
                </button>
                <button @click="handleConfirm" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</template>