<script setup>
import { defineProps, defineEmits, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    reservationId: {
        type: [Number, String],
        required: true
    },
    // Nuevas props para recibir datos existentes
    existingData: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['close', 'update-payment']);
const cost = ref(0);
const isPaid = ref(false);
const paymentDate = ref('');
const isLoading = ref(false);
const errorMessage = ref('');
const showSuccessAlert = ref(false);

// Variable para controlar la cancelación con Axios
let cancelTokenSource = null;

// Watch para cargar datos existentes cuando el modal se abre
watch(() => props.show, (newValue) => {
    if (newValue && props.existingData) {
        cost.value = props.existingData.cost || 0;
        isPaid.value = props.existingData.is_paid || false;
        paymentDate.value = props.existingData.payment_date || '';
        errorMessage.value = '';
        showSuccessAlert.value = false;
    }
});

const closeModal = () => {
    // Cancelar petición activa si existe
    if (cancelTokenSource && isLoading.value) {
        cancelTokenSource.cancel('Operación cancelada por el usuario');
        console.log('Operación de guardado cancelada');
    }
    
    // Resetear estado de loading y errores, pero mantener los datos del formulario
    isLoading.value = false;
    errorMessage.value = '';
    showSuccessAlert.value = false;
    cancelTokenSource = null;
    
    emit('close');
};

const savePaymentDetails = async () => {
    if (!props.reservationId) {
        errorMessage.value = 'Error: ID de reservación no disponible';
        return;
    }

    if (isPaid.value && !paymentDate.value) {
        errorMessage.value = 'Por favor, selecciona una fecha de pago si está marcado como pagado.';
        return;
    }

    // Evitar múltiples envíos
    if (isLoading.value) return;

    const data = {
        reservation_id: props.reservationId,
        cost: cost.value,
        is_paid: isPaid.value,
        payment_date: paymentDate.value || null
    };

    console.log('Datos a enviar:', data);

    isLoading.value = true;
    errorMessage.value = '';

    try {
        // Crear token de cancelación para Axios
        cancelTokenSource = axios.CancelToken.source();

        const response = await axios.post(route('admin.general.events.update-payment'), data, {
            cancelToken: cancelTokenSource.token
        });
        
        console.log('Respuesta del servidor:', response.data);
        
        // Emitir evento para actualizar los datos en el componente padre
        emit('update-payment', {
            id: props.reservationId,
            ...data
        });
        
        // Mostrar alerta de éxito
        showSuccessAlert.value = true;
        
    } catch (error) {
        // Verificar si fue cancelado
        if (axios.isCancel(error)) {
            console.log('Petición cancelada:', error.message);
            return; // No mostrar error si fue cancelado intencionalmente
        }
        
        console.error('Error al guardar los detalles de pago:', error);
        console.log('Respuesta de error completa:', error.response);
        errorMessage.value = error.response?.data?.error || 'Error al guardar los detalles de pago. Intenta de nuevo.';
        
    } finally {
        isLoading.value = false;
        cancelTokenSource = null;
    }
};

const closeSuccessAlert = () => {
    showSuccessAlert.value = false;
    // Cerrar el modal después de cerrar la alerta
    setTimeout(() => {
        closeModal();
    }, 100);
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-md max-h-[90vh] overflow-y-auto">
            <!-- Alerta de éxito -->
            <div v-if="showSuccessAlert" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center z-10 rounded-lg">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 mx-4 max-w-sm w-full">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 text-center mb-2">
                        ¡Éxito!
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-6">
                        Los detalles de pago se guardaron correctamente.
                    </p>
                    <button
                        @click="closeSuccessAlert"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                    >
                        Aceptar
                    </button>
                </div>
            </div>

            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                    Detalles de Pago
                </h2>
                <button 
                    @click="closeModal" 
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    :disabled="showSuccessAlert"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="p-6 space-y-4">
                <!-- Mensaje de error -->
                <div v-if="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ errorMessage }}</span>
                </div>

                <!-- Costo -->
                <div>
                    <label for="cost" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Costo:
                    </label>
                    <input
                        id="cost"
                        v-model.number="cost"
                        type="number"
                        min="0"
                        step="0.01"
                        class="w-full px-3 py-2 text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="0.00"
                        :disabled="isLoading || showSuccessAlert"
                    />
                </div>

                <!-- Pagado -->
                <div class="flex items-center">
                    <input
                        id="isPaid"
                        v-model="isPaid"
                        type="checkbox"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        :disabled="isLoading || showSuccessAlert"
                    />
                    <label for="isPaid" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Pagado
                    </label>
                </div>

                <!-- Fecha de pago -->
                <div>
                    <label for="paymentDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Fecha de pago:
                    </label>
                    <input
                        id="paymentDate"
                        v-model="paymentDate"
                        type="date"
                        class="w-full px-3 py-2 text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        :disabled="!isPaid || isLoading || showSuccessAlert"
                    />
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end space-x-3 p-4 border-t dark:border-gray-700">
                <button
                    @click="closeModal"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition-colors"
                    :disabled="showSuccessAlert"
                >
                    Cancelar
                </button>
                <button
                    @click="savePaymentDetails"
                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="isLoading || showSuccessAlert"
                >
                    <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ isLoading ? 'Guardando...' : 'Guardar cambios' }}
                </button>
            </div>
        </div>
    </div>
</template>