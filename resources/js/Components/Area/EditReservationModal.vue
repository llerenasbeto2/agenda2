<script setup>
import { ref, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    reservation: Object,
    faculties: Array,
    classrooms: Array,
    categorie: Array,
    municipalities: Array
});

const emit = defineEmits(['close']);

// Formatear fechas para datetime-local
const formatDateTimeForInput = (dateTime) => {
    if (!dateTime) return '';
    const date = new Date(dateTime);
    if (isNaN(date.getTime())) return '';
    const pad = (num) => num.toString().padStart(2, '0');
    return `${date.getUTCFullYear()}-${pad(date.getUTCMonth() + 1)}-${pad(date.getUTCDate())}T${pad(date.getUTCHours())}:${pad(date.getUTCMinutes())}`;
};

// Estados
// const showRecurringOptions = ref(false);
// const selectedDays = ref([]);
const irregularDates = ref([]);
const newIrregularDate = ref({ date: '', startTime: '', endTime: '' });

// Inicializar formulario con datos de la reservación
const form = useForm({
    id: props.reservation?.id || '',
    full_name: props.reservation?.full_name || '',
    Email: props.reservation?.Email || '',
    phone: props.reservation?.phone || '',
    faculty_id: props.reservation?.faculty_id || '',
    municipality_id: props.reservation?.municipality_id || '',
    classroom_id: props.reservation?.classroom_id || '',
    event_title: props.reservation?.event_title || '',
    category_type: props.reservation?.category_type || '',
    attendees: props.reservation?.attendees || '',
    start_datetime: props.reservation?.start_datetime ? formatDateTimeForInput(props.reservation.start_datetime) : '',
    end_datetime: props.reservation?.end_datetime ? formatDateTimeForInput(props.reservation.end_datetime) : '',
    requirements: props.reservation?.requirements || '',
    status: props.reservation?.status || 'Pendiente',
    // is_recurring: props.reservation?.is_recurring || false,
    // repeticion: props.reservation?.repeticion || 1,
    // recurring_frequency: props.reservation?.recurring_frequency || 'weekly',
    // recurring_days: props.reservation?.recurring_days || '',
    // recurring_end_date: props.reservation?.recurring_end_date ? formatDateTimeForInput(props.reservation.recurring_end_date) : '',
    irregular_dates: props.reservation?.irregular_dates || ''
});

// Mostrar/ocultar opciones recurrentes (eliminado)
// const toggleRecurringOptions = () => {
//     showRecurringOptions.value = form.is_recurring;
//     if (!form.is_recurring) {
//         form.repeticion = 1;
//         form.recurring_frequency = 'weekly';
//         form.recurring_days = '';
//         form.recurring_end_date = '';
//         form.irregular_dates = '';
//         selectedDays.value = [];
//         irregularDates.value = [];
//     }
// };

// Actualizar días recurrentes (eliminado)
// const updateRecurringDays = () => {
//     form.recurring_days = JSON.stringify(selectedDays.value);
// };

// Agregar fecha irregular
const addIrregularDate = () => {
    if (newIrregularDate.value.date && newIrregularDate.value.startTime && newIrregularDate.value.endTime) {
        irregularDates.value.push({
            date: newIrregularDate.value.date,
            startTime: newIrregularDate.value.startTime,
            endTime: newIrregularDate.value.endTime,
            displayText: `${newIrregularDate.value.date} ${newIrregularDate.value.startTime} - ${newIrregularDate.value.endTime}`
        });
        newIrregularDate.value = { date: '', startTime: '', endTime: '' };
    }
};

// Eliminar fecha irregular
const removeIrregularDate = (index) => {
    irregularDates.value.splice(index, 1);
};

// Cargar datos al montar
onMounted(() => {
    if (props.reservation) {
        // Cargar fechas irregulares
        if (props.reservation.irregular_dates) {
            irregularDates.value = Array.isArray(props.reservation.irregular_dates)
                ? props.reservation.irregular_dates
                : JSON.parse(props.reservation.irregular_dates || '[]');
        }
    }
});

// Observar cambios en selectedDays (eliminado)
// watch(selectedDays, () => {
//     updateRecurringDays();
// });

// Preparar datos para envío
const prepareSubmit = () => {
    form.irregular_dates = irregularDates.value.length > 0 ? JSON.stringify(irregularDates.value) : null;
    // form.recurring_days = selectedDays.value.length > 0 ? JSON.stringify(selectedDays.value) : null;
    form.start_datetime = new Date(form.start_datetime).toISOString();
    form.end_datetime = form.end_datetime ? new Date(form.end_datetime).toISOString() : null;
    // if (form.recurring_end_date) {
    //     form.recurring_end_date = new Date(form.recurring_end_date).toISOString();
    // }
};

// Enviar formulario
const submit = () => {
    prepareSubmit();
    form.put(route('admin.area.events.update', form.id), {
        onSuccess: () => {
            emit('close');
        },
        onError: (errors) => {
            console.error('Errores:', errors);
        }
    });
};

// Cerrar modal
const close = () => {
    emit('close');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 overflow-y-auto z-50">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75" @click="close"></div>
            </div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-200 mb-4">
                        Editar Reservación
                    </h3>

                    <form @submit.prevent="submit">
                        <!-- Información del solicitante -->
                        <div class="mb-6">
                            <h3 class="font-semibold text-lg mb-4 text-gray-800 dark:text-gray-200 border-b pb-2">
                                Información del Solicitante
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre Completo</label>
                                    <input id="full_name" v-model="form.full_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required />
                                    <div v-if="form.errors.full_name" class="text-red-500 text-sm mt-1">{{ form.errors.full_name }}</div>
                                </div>
                                <div>
                                    <label for="Email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Correo Electrónico</label>
                                    <input id="Email" v-model="form.Email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required />
                                    <div v-if="form.errors.Email" class="text-red-500 text-sm mt-1">{{ form.errors.Email }}</div>
                                </div>
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
                                    <input id="phone" v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required />
                                    <div v-if="form.errors.phone" class="text-red-500 text-sm mt-1">{{ form.errors.phone }}</div>
                                </div>
                                <div>
                                    <label for="faculty_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Facultad</label>
                                    <select id="faculty_id" v-model="form.faculty_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                        <option value="">Seleccione una facultad</option>
                                        <option v-for="faculty in faculties" :key="faculty.id" :value="faculty.id">{{ faculty.name }}</option>
                                    </select>
                                    <div v-if="form.errors.faculty_id" class="text-red-500 text-sm mt-1">{{ form.errors.faculty_id }}</div>
                                </div>
                                <div>
                                    <label for="municipality_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Municipio</label>
                                    <select id="municipality_id" v-model="form.municipality_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                        <option value="">Seleccione un municipio</option>
                                        <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">{{ municipality.name }}</option>
                                    </select>
                                    <div v-if="form.errors.municipality_id" class="text-red-500 text-sm mt-1">{{ form.errors.municipality_id }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Información del evento -->
                        <div class="mb-6">
                            <h3 class="font-semibold text-lg mb-4 text-gray-800 dark:text-gray-200 border-b pb-2">
                                Información del Evento
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="event_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título del Evento</label>
                                    <input id="event_title" v-model="form.event_title" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required />
                                    <div v-if="form.errors.event_title" class="text-red-500 text-sm mt-1">{{ form.errors.event_title }}</div>
                                </div>
                                <div>
                                    <label for="category_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Evento</label>
                                    <select id="category_type" v-model="form.category_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                        <option value="">Seleccione una categoría</option>
                                        <option v-for="category in categorie" :key="category.id" :value="category.id">{{ category.name }}</option>
                                    </select>
                                    <div v-if="form.errors.category_type" class="text-red-500 text-sm mt-1">{{ form.errors.category_type }}</div>
                                </div>
                                <div>
                                    <label for="classroom_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Escenario/Aula</label>
                                    <select id="classroom_id" v-model="form.classroom_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                        <option value="">Seleccione un aula</option>
                                        <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">{{ classroom.name }}</option>
                                    </select>
                                    <div v-if="form.errors.classroom_id" class="text-red-500 text-sm mt-1">{{ form.errors.classroom_id }}</div>
                                </div>
                                <div>
                                    <label for="attendees" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número de Asistentes</label>
                                    <input id="attendees" v-model="form.attendees" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required />
                                    <div v-if="form.errors.attendees" class="text-red-500 text-sm mt-1">{{ form.errors.attendees }}</div>
                                </div>
                                <div>
                                    <label for="start_datetime" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha y Hora de Inicio</label>
                                    <input id="start_datetime" v-model="form.start_datetime" type="datetime-local" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required />
                                    <div v-if="form.errors.start_datetime" class="text-red-500 text-sm mt-1">{{ form.errors.start_datetime }}</div>
                                </div>
                                <div>
                                    <label for="end_datetime" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha y Hora de Fin</label>
                                    <input id="end_datetime" v-model="form.end_datetime" type="datetime-local" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                    <div v-if="form.errors.end_datetime" class="text-red-500 text-sm mt-1">{{ form.errors.end_datetime }}</div>
                                </div>
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                                    <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                        <option value="pendiente">Pendiente</option>
                                        <option value="Aprobado">Aprobado</option>
                                        <option value="rechazado">Rechazado</option>
                                        <option value="cancelado">Cancelado</option>
                                        <option value="no_realizado">No Realizado</option>
                                        <option value="realizado">Realizado</option>
                                    </select>
                                    <div v-if="form.errors.status" class="text-red-500 text-sm mt-1">{{ form.errors.status }}</div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label for="requirements" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Requerimientos</label>
                                <textarea id="requirements" v-model="form.requirements" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                <div v-if="form.errors.requirements" class="text-red-500 text-sm mt-1">{{ form.errors.requirements }}</div>
                            </div>
                            <!-- Checkbox eliminado -->
                            <!-- <div class="mt-4">
                                <label for="is_recurring" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Evento Recurrente</label>
                                <input id="is_recurring" v-model="form.is_recurring" type="checkbox" @change="toggleRecurringOptions" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600" />
                            </div> -->
                        </div>

                        <!-- Opciones de recurrencia -->
                        <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h3 class="font-semibold text-lg mb-4 text-gray-800 dark:text-gray-200 border-b pb-2">Opciones de Recurrencia</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- <div>
                                    <label for="repeticion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Repeticiones</label>
                                    <input id="repeticion" v-model="form.repeticion" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                    <div v-if="form.errors.repeticion" class="text-red-500 text-sm mt-1">{{ form.errors.repeticion }}</div>
                                </div> -->
                                <!-- <div>
                                    <label for="recurring_frequency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Frecuencia</label>
                                    <select id="recurring_frequency" v-model="form.recurring_frequency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="daily">Diaria</option>
                                        <option value="weekly">Semanal</option>
                                        <option value="biweekly">Quincenal</option>
                                        <option value="monthly">Mensual</option>
                                    </select>
                                    <div v-if="form.errors.recurring_frequency" class="text-red-500 text-sm mt-1">{{ form.errors.recurring_frequency }}</div>
                                </div> -->
                                <!-- <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Días Recurrentes</label>
                                    <div class="flex flex-wrap gap-2">
                                        <div v-for="(day, index) in ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']" :key="index" class="flex items-center">
                                            <input :id="`day-${index}`" type="checkbox" :value="day" v-model="selectedDays" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600" />
                                            <label :for="`day-${index}`" class="ml-1 text-sm text-gray-700 dark:text-gray-300">{{ day }}</label>
                                        </div>
                                    </div>
                                    <div v-if="form.errors.recurring_days" class="text-red-500 text-sm mt-1">{{ form.errors.recurring_days }}</div>
                                </div> -->
                                <!-- <div>
                                    <label for="recurring_end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha Fin de Recurrencia</label>
                                    <input id="recurring_end_date" v-model="form.recurring_end_date" type="datetime-local" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                    <div v-if="form.errors.recurring_end_date" class="text-red-500 text-sm mt-1">{{ form.errors.recurring_end_date }}</div>
                                </div> -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fechas Irregulares</label>
                                    <div class="flex flex-wrap gap-2 mb-4">
                                        <input type="date" v-model="newIrregularDate.date" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                        <input type="time" v-model="newIrregularDate.startTime" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                        <input type="time" v-model="newIrregularDate.endTime" class="rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                                        <button type="button" @click="addIrregularDate" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Agregar</button>
                                    </div>
                                    <div v-if="irregularDates.length" class="mt-4">
                                        <table class="min-w-full bg-white dark:bg-gray-700 rounded-lg overflow-hidden">
                                            <thead class="bg-gray-100 dark:bg-gray-600">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-gray-700 dark:text-gray-200">Fecha</th>
                                                    <th class="px-4 py-2 text-left text-gray-700 dark:text-gray-200">Inicio</th>
                                                    <th class="px-4 py-2 text-left text-gray-700 dark:text-gray-200">Fin</th>
                                                    <th class="px-4 py-2 text-left text-gray-700 dark:text-gray-200">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(date, index) in irregularDates" :key="index" class="border-b dark:border-gray-600">
                                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                                                        <input type="date" v-model="date.date" class="bg-transparent border-b border-gray-300 dark:border-gray-500 focus:border-blue-500" />
                                                    </td>
                                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                                                        <input type="time" v-model="date.startTime" class="bg-transparent border-b border-gray-300 dark:border-gray-500 focus:border-blue-500" />
                                                    </td>
                                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                                                        <input type="time" v-model="date.endTime" class="bg-transparent border-b border-gray-300 dark:border-gray-500 focus:border-blue-500" />
                                                    </td>
                                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-200">
                                                        <button @click="removeIrregularDate(index)" class="text-red-500 hover:text-red-700">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div v-if="form.errors.irregular_dates" class="text-red-500 text-sm mt-1">{{ form.errors.irregular_dates }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex justify-end gap-4 mt-6">
                            <button type="button" @click="close" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition-colors">Cancelar</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors" :disabled="form.processing">{{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>