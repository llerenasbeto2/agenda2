<script setup>
import { ref, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    reservation: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['statusChanged']);

// Estado para el modal de confirmación y carga
const showAlert = ref(false);
const statusToChange = ref('');
const isLoading = ref(false); // Estado para manejar la carga
const form = useForm({
    status: ''
}); // Definir form fuera de la función para mantener la referencia

const statusTitles = {
    'Pendiente': 'Pendiente',
    'Aprobado': 'Aprobado',
    'Rechazado': 'Rechazado',
    'Cancelado': 'Cancelado',
    'No_realizado': 'No Realizado',
    'Realizado': 'Realizado'
};

// Abre la alerta de confirmación
const confirmStatusChange = (status) => {
    statusToChange.value = status;
    showAlert.value = true;
};

// Procesa el cambio de estado
const changeStatus = () => {
    if (isLoading.value) return; // Evita múltiples clics mientras está cargando
    isLoading.value = true; // Activa el estado de carga

    console.log('Reservation ID:', props.reservation.id);
    console.log('Status to change:', statusToChange.value);

    form.status = statusToChange.value; // Actualiza el estado en el form

    form.patch(route('admin.general.events.cambiarEstado', props.reservation.id), {
        onSuccess: () => {
            console.log('Estado actualizado con éxito');
            emit('statusChanged', {
                id: props.reservation.id,
                status: statusToChange.value
            });
        },
        onError: (errors) => {
            console.error('Error al actualizar el estado:', errors);
            isLoading.value = false; // Desactiva la carga en caso de error
        },
        onFinish: async () => {
            console.log('Solicitud finalizada');
            showAlert.value = false;
            isLoading.value = false; // Desactiva la carga al finalizar
            await nextTick();
        }
    });
};

// Cierra la alerta y cancela la operación
const cancelStatusChange = () => {
    form.cancel(); // Cancela la solicitud activa
    showAlert.value = false;
    isLoading.value = false; // Resetea el estado de carga
};
</script>

<template>
    <div>
        <!-- Grupo de botones de estado -->
        <div class="flex space-x-1">
            <button 
                @click="confirmStatusChange('Pendiente')" 
                class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold p-2 rounded-full text-sm" 
                title="Pendiente"
                :class="{ 'ring-2 ring-offset-2 ring-yellow-300': props.reservation.status === 'Pendiente' }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                    <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
                </svg>
            </button>
            <button 
                @click="confirmStatusChange('Aprobado')" 
                class="bg-green-500 hover:bg-green-700 text-white font-bold p-2 rounded-full text-sm" 
                title="Aprobado"
                :class="{ 'ring-2 ring-offset-2 ring-green-300': props.reservation.status === 'Aprobado' }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                    <path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05"/>
                </svg>
            </button>
            <button 
                @click="confirmStatusChange('Rechazado')" 
                class="bg-red-500 hover:bg-red-700 text-white font-bold p-2 rounded-full text-sm" 
                title="Rechazado"
                :class="{ 'ring-2 ring-offset-2 ring-red-300': props.reservation.status === 'Rechazado' }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <button 
                @click="confirmStatusChange('Cancelado')" 
                class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold p-2 rounded-full text-sm" 
                title="Cancelado"
                :class="{ 'ring-2 ring-offset-2 ring-yellow-300': props.reservation.status === 'Cancelado' }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </button>
            <button 
                @click="confirmStatusChange('No_realizado')" 
                class="bg-purple-500 hover:bg-purple-700 text-white font-bold p-2 rounded-full text-sm" 
                title="No realizado"
                :class="{ 'ring-2 ring-offset-2 ring-purple-300': props.reservation.status === 'No_realizado' }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </button>
            <button 
                @click="confirmStatusChange('Realizado')" 
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold p-2 rounded-full text-sm" 
                title="Realizado"
                :class="{ 'ring-2 ring-offset-2 ring-blue-300': props.reservation.status === 'Realizado' }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </button>
        </div>

        <!-- Modal de confirmación -->
        <div v-if="showAlert" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black opacity-50"></div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-sm mx-auto z-10 shadow-xl">
                <div class="text-xl font-bold mb-4 text-gray-800 dark:text-gray-200">
                    Confirmar cambio de estado
                </div>
                <div class="mb-6 text-gray-600 dark:text-gray-300">
                    ¿Estás seguro de cambiar el estado de esta reservación a <span class="font-semibold">{{ statusTitles[statusToChange] }}</span>?
                </div>
                <div class="flex justify-end space-x-3">
                    <button 
                        @click="cancelStatusChange" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded"
                    >
                        Cancelar
                    </button>
                    <button 
                        @click="changeStatus" 
                        class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded flex items-center"
                        :disabled="isLoading"
                    >
                        <span v-if="isLoading" class="animate-spin mr-2">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </span>
                        {{ isLoading ? 'Procesando...' : 'Confirmar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>