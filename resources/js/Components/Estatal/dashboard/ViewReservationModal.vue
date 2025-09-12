<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: Boolean,
    reservation: Object
});

const emit = defineEmits(['close']);

const closeModal = () => {
    emit('close');
};

const formatFecha = (fecha) => {
    if (!fecha) return '';
    
    try {
        const date = new Date(fecha);
        if (isNaN(date.getTime())) {
            console.error('Fecha inválida:', fecha);
            return 'Fecha inválida';
        }
        
        const options = { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
            timeZone: 'UTC'
        };
        
        return date.toLocaleString('es-MX', options);
    } catch (error) {
        console.error('Error al formatear fecha:', error, fecha);
        return 'Fecha inválida';
    }
};



const formatDateOnly = (fecha) => {
    if (!fecha) return '';
    
    try {
        let date;
        // Si es una cadena en formato ISO (con T y Z), extraer solo la parte de la fecha
        if (typeof fecha === 'string' && fecha.includes('T')) {
            const [datePart] = fecha.split('T'); // Toma solo "2025-05-25"
            const [year, month, day] = datePart.split('-');
            if (!year || !month || !day) throw new Error('Formato de fecha inválido');
            date = new Date(year, month - 1, day);
        } else if (fecha instanceof Date && !isNaN(fecha.getTime())) {
            date = fecha;
        } else {
            // Intentar parsear como YYYY-MM-DD si no tiene T
            const [year, month, day] = fecha.split('-');
            if (!year || !month || !day) throw new Error('Formato de fecha inválido');
            date = new Date(year, month - 1, day);
        }
        
        if (isNaN(date.getTime())) {
            console.error('Fecha inválida:', fecha);
            return 'Fecha inválida';
        }
        
        const options = { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric'
        };
        
        return date.toLocaleDateString('es-MX', options);
    } catch (error) {
        console.error('Error al formatear fecha:', error, fecha);
        return 'Fecha inválida';
    }
};


const statusColor = computed(() => {
    if (!props.reservation) return '';
    
    const status = props.reservation.status;
    if (status === 'Pendiente') return 'bg-yellow-100 text-yellow-800';
    if (['Aprobado', 'Aprobado', 'Realizado'].includes(status)) return 'bg-green-100 text-green-800';
    if (['Rechazado', 'Cancelado', 'No_realizado'].includes(status)) return 'bg-red-100 text-red-800';
    return '';
});

const formatIrregularDates = computed(() => {
    if (!props.reservation || !props.reservation.irregular_dates) return [];
    
    try {
        if (Array.isArray(props.reservation.irregular_dates)) {
            return props.reservation.irregular_dates.map(date => {
                return date.displayText || 
                    (date.date && date.startTime && date.endTime 
                        ? `${date.date} ${date.startTime} - ${date.endTime}` 
                        : 'Fecha no especificada');
            });
        }
        
        if (typeof props.reservation.irregular_dates === 'string') {
            const dates = JSON.parse(props.reservation.irregular_dates);
            if (Array.isArray(dates)) {
                return dates.map(date => {
                    return date.displayText || 
                        (date.date && date.startTime && date.endTime 
                            ? `${date.date} ${date.startTime} - ${date.endTime}` 
                            : 'Fecha no especificada');
                });
            }
        }
        
        return [];
    } catch (error) {
        console.error('Error parsing irregular dates:', error);
        return [];
    }
});

const formatRecurringDays = computed(() => {
    if (!props.reservation || !props.reservation.recurring_days) return [];
    
    try {
        if (Array.isArray(props.reservation.recurring_days)) {
            return props.reservation.recurring_days;
        }
        
        if (typeof props.reservation.recurring_days === 'string') {
            const days = JSON.parse(props.reservation.recurring_days);
            if (Array.isArray(days)) {
                return days;
            }
        }
        
        return [];
    } catch (error) {
        console.error('Error parsing recurring days:', error);
        return [];
    }
});
</script>

<template>
    <div v-if="show && reservation" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                    Detalles de Reservación
                </h2>
                <button @click="closeModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Información general -->
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Información General</h3>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg space-y-3">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Título del Evento</p>
                                    <p class="text-gray-800 dark:text-gray-200 font-medium">{{ reservation.event_title }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Estado</p>
                                    <span :class="[statusColor, 'px-2 py-1 rounded-full text-xs font-semibold inline-block']">
                                        {{ reservation.status }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Categoría</p>
                                    <p class="text-gray-800 dark:text-gray-200">{{ reservation.category?.name || 'No especificada' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Número de personas</p>
                                    <p class="text-gray-800 dark:text-gray-200">{{ reservation.attendees }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Solicitante -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Información del Solicitante</h3>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg space-y-3">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Nombre Completo</p>
                                    <p class="text-gray-800 dark:text-gray-200 font-medium">{{ reservation.full_name }}</p>
                                </div>
                                <div v-if="reservation.email">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Correo Electrónico</p>
                                    <p class="text-gray-800 dark:text-gray-200">{{ reservation.email }}</p>
                                </div>
                                <div v-if="reservation.phone">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Teléfono</p>
                                    <p class="text-gray-800 dark:text-gray-200">{{ reservation.phone }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información de Pago -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Información de Pago</h3>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg space-y-3">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Costo</p>
                                    <p class="text-gray-800 dark:text-gray-200">{{ reservation.cost != null && !isNaN(reservation.cost) ? `$${Number(reservation.cost).toFixed(2)}` : '$0.00' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Estado de Pago</p>
                                    <p class="text-gray-800 dark:text-gray-200">{{ reservation.is_paid ? 'Pagado' : 'No Pagado' }}</p>
                                </div>
                                <div v-if="reservation.payment_date">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Fecha de Pago</p>
                                    <p class="text-gray-800 dark:text-gray-200">{{ formatDateOnly(reservation.payment_date) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detalles de la reservación -->
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Detalles de la Reservación</h3>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg space-y-3">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Facultad</p>
                                    <p class="text-gray-800 dark:text-gray-200 font-medium">{{ reservation.faculty?.name || 'No especificada' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Municipio</p>
                                    <p class="text-gray-800 dark:text-gray-200 font-medium">{{ reservation.municipality?.name || 'No especificado' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Aula</p>
                                    <p class="text-gray-800 dark:text-gray-200 font-medium">{{ reservation.classroom?.name || 'No especificada' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Fecha de Inicio</p>
                                    <p class="text-gray-800 dark:text-gray-200">{{ formatFecha(reservation.start_datetime) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Fecha de Fin</p>
                                    <p class="text-gray-800 dark:text-gray-200">{{ formatFecha(reservation.end_datetime) }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Detalles de Repetición -->
                        <div v-if="reservation.is_recurring">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Detalles de Repetición</h3>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg space-y-3">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Es Recurrente</p>
                                    <p class="text-gray-800 dark:text-gray-200">{{ reservation.is_recurring ? 'Sí' : 'No' }}</p>
                                </div>
                                <div v-if="reservation.repeticion">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Número de Repeticiones</p>
                                    <p class="text-gray-800 dark:text-gray-200">{{ reservation.repeticion }}</p>
                                </div>
                                <div v-if="reservation.recurring_frequency">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Frecuencia de Repetición</p>
                                    <p class="text-gray-800 dark:text-gray-200">{{ reservation.recurring_frequency }}</p>
                                </div>
                                <div v-if="reservation.recurring_end_date">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Fecha de Fin de Repetición</p>
                                    <p class="text-gray-800 dark:text-gray-200">{{ formatFecha(reservation.recurring_end_date) }}</p>
                                </div>
                                <div v-if="formatRecurringDays.length > 0">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Días de Repetición</p>
                                    <ul class="text-gray-800 dark:text-gray-200 list-disc pl-5">
                                        <li v-for="(day, index) in formatRecurringDays" :key="index">
                                            {{ day }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Fechas Adicionales -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Fechas Adicionales</h3>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg space-y-3">
                                <div v-if="formatIrregularDates.length > 0">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Fechas Irregulares</p>
                                    <ul class="text-gray-800 dark:text-gray-200 list-disc pl-5">
                                        <li v-for="(date, index) in formatIrregularDates" :key="index">
                                            {{ date }}
                                        </li>
                                    </ul>
                                </div>
                                <div v-else>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay fechas irregulares</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Requisitos -->
                        <div v-if="reservation.requirements">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Requisitos</h3>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg space-y-3">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Requisitos Solicitados</p>
                                    <p class="text-gray-800 dark:text-gray-200">{{ reservation.requirements }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Comentarios Administrativos -->
                <div class="mt-6" v-if="reservation.admin_comments">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Comentarios Administrativos</h3>
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <p class="text-gray-800 dark:text-gray-200">{{ reservation.admin_comments }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="flex justify-end p-4 border-t dark:border-gray-700">
                <button @click="closeModal" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</template>