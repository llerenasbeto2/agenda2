<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: { type: Boolean, default: false },
    reservationId: { type: [Number, String], required: true },
});

const emit = defineEmits(['close', 'send-comment']);
const comment = ref('');
const isLoading = ref(false);
const errorMessage = ref('');

const closeModal = () => {
    comment.value = '';
    errorMessage.value = '';
    emit('close');
};

const sendComment = async () => {
    if (!comment.value.trim()) {
        errorMessage.value = 'Por favor, escribe un comentario antes de enviar.';
        return;
    }

    if (!props.reservationId) {
        errorMessage.value = 'Error: ID de reservación no disponible';
        return;
    }

    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.post(route('admin.area.events.send-comment'), {
            reservation_id: props.reservationId,
            comment: comment.value.trim(),
        });

        console.log('Comment sent successfully:', response.data);
        emit('send-comment', comment.value.trim());
        comment.value = '';
        closeModal();
        alert('Comentario enviado correctamente');
    } catch (error) {
        console.error('Error sending comment:', error.response?.data || error);
        errorMessage.value = error.response?.data?.error || 'Error al enviar el comentario. Intenta de nuevo.';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                    Enviar Comentario
                </h2>
                <button @click="closeModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <div class="space-y-4">
                    <!-- Mensaje de error -->
                    <div v-if="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ errorMessage }}</span>
                    </div>
                    
                    <div>
                        <label for="comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Comentario
                        </label>
                        <textarea 
                            id="comment"
                            v-model="comment"
                            rows="7"
                            class="w-full px-3 py-2 text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none overflow-y-auto max-h-32"
                            placeholder="Escribe tu comentario aquí..."
                        ></textarea>  
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="flex justify-end space-x-3 p-4 border-t dark:border-gray-700">
                <button 
                    @click="closeModal" 
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 dark:text-gray-200 dark:bg-gray-600 dark:hover:bg-gray-500 font-bold py-2 px-4 rounded"
                    :disabled="isLoading"
                >
                    Cancelar
                </button>
                <button 
                    @click="sendComment"
                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded flex items-center justify-center"
                    :disabled="isLoading"
                >
                    <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ isLoading ? 'Enviando...' : 'Enviar' }}
                </button>
            </div>
        </div>
    </div>
</template>