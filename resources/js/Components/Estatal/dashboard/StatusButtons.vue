<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    reservation: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['statusChanged']);
const showAlert = ref(false);
const statusToChange = ref('');
const statusTitles = {
    Pendiente: 'Pendiente',
    Aprobado: 'Aprobado',
    Rechazado: 'Rechazado',
    Cancelado: 'Cancelado',
    No_realizado: 'No Realizado',
    Realizado: 'Realizado',
};

const confirmStatusChange = (status) => {
    statusToChange.value = status;
    showAlert.value = true;
};

const changeStatus = () => {
    const form = useForm({
        status: statusToChange.value,
    });

    form.patch(route('admin.estatal.events.cambiarEstado', { id: props.reservation.id }), {
        onSuccess: () => {
            console.log(`Status changed to ${statusToChange.value} for reservation ${props.reservation.id}`);
            emit('statusChanged', {
                id: props.reservation.id,
                status: statusToChange.value,
            });
            showAlert.value = false;
        },
        onError: (errors) => {
            console.error('Error changing status:', errors);
            alert('No se pudo cambiar el estado. Por favor, intenta de nuevo.');
        },
    });
};

const cancelStatusChange = () => {
    showAlert.value = false;
};
</script>

<template>
    <div>
        <!-- Grupo de botones de estado -->
        <div class="flex space-x-2">
            <!-- Botón Pendiente -->
            <button
                @click="confirmStatusChange('Pendiente')"
                class="bg-yellow-500 hover:bg-yellow-600 text-white p-2 rounded-full transition-colors duration-200"
                title="Pendiente"
                :class="{ 'ring-2 ring-offset-2 ring-yellow-300': props.reservation.status === 'Pendiente' }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>

            <!-- Botón Aprobado -->
            <button
                @click="confirmStatusChange('Aprobado')"
                class="bg-green-500 hover:bg-green-600 text-white p-2 rounded-full transition-colors duration-200"
                title="Aprobado"
                :class="{ 'ring-2 ring-offset-2 ring-green-300': props.reservation.status === 'Aprobado' }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </button>

            <!-- Botón Rechazado -->
            <button
                @click="confirmStatusChange('Rechazado')"
                class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-full transition-colors duration-200"
                title="Rechazado"
                :class="{ 'ring-2 ring-offset-2 ring-red-300': props.reservation.status === 'Rechazado' }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Botón Cancelado -->
            <button
                @click="confirmStatusChange('Cancelado')"
                class="bg-orange-500 hover:bg-orange-600 text-white p-2 rounded-full transition-colors duration-200"
                title="Cancelado"
                :class="{ 'ring-2 ring-offset-2 ring-orange-300': props.reservation.status === 'Cancelado' }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>

            <!-- Botón No Realizado -->
            <button
                @click="confirmStatusChange('No_realizado')"
                class="bg-purple-500 hover:bg-purple-600 text-white p-2 rounded-full transition-colors duration-200"
                title="No Realizado"
                :class="{ 'ring-2 ring-offset-2 ring-purple-300': props.reservation.status === 'No_realizado' }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </button>

            <!-- Botón Realizado -->
            <button
                @click="confirmStatusChange('Realizado')"
                class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-full transition-colors duration-200"
                title="Realizado"
                :class="{ 'ring-2 ring-offset-2 ring-blue-300': props.reservation.status === 'Realizado' }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
        </div>

        <!-- Modal de confirmación -->
        <div v-if="showAlert" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200 mb-4">
                    Confirmar cambio de estado
                </h3>
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    ¿Estás seguro de cambiar el estado de esta reservación a
                    <span class="font-semibold text-blue-600 dark:text-blue-400">{{ statusTitles[statusToChange] }}</span>?
                </p>
                <div class="flex justify-end space-x-3">
                    <button
                        @click="cancelStatusChange"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="changeStatus"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                    >
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>