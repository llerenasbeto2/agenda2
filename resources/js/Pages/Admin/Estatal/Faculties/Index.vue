<script setup>
import AdminEstatalLayout from '@/Layouts/AdminEstatalLayout.vue';
import CityButtonGroup from '@/Components/CityButtonGroup.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    faculties: Array,
    classrooms: Array,
    municipalities: Array,
    selectedMunicipality: Number,
    error: String,
});

const isLoading = ref(false);
const showImageModal = ref(false);
const selectedImage = ref(null);
const showDetailsModal = ref(false);
const selectedClassroom = ref(null);

// Variable reactiva para el municipio seleccionado
const selectedMunicipality = ref(props.selectedMunicipality || 1);

// Propiedad computada para filtrar aulas por municipio (si necesitas esta funcionalidad)
const filteredClassrooms = computed(() => {
    if (!props.classrooms) return [];
    
    // Si hay municipio seleccionado y las aulas tienen información del municipio
    if (selectedMunicipality.value && props.classrooms[0]?.faculty?.municipality_id) {
        return props.classrooms.filter(classroom => {
            return Number(classroom.faculty.municipality_id) === Number(selectedMunicipality.value);
        });
    }
    
    // Si no, devolver todas las aulas
    return props.classrooms;
});

// Depuración inicial de los datos recibidos
console.log('Props Classrooms:', props.classrooms);
console.log('Props Municipalities:', props.municipalities);
console.log('Props Faculties:', props.faculties);
console.log('Initial Selected Municipality:', selectedMunicipality.value);

// Observar cambios en selectedMunicipality para depurar
watch(selectedMunicipality, (newValue) => {
    console.log('Selected Municipality Changed:', newValue);
});

const destroy = (id) => {
    if (confirm('¿Estás seguro de que quieres eliminar esta aula?')) {
        isLoading.value = true;
        router.delete(route('admin.estatal.faculties.destroy', id), {
            onFinish: () => { isLoading.value = false; }
        });
    }
};

// Manejar la selección de municipio (si se necesita esta funcionalidad)
const selectCity = (municipalityId) => {
    console.log('City Selected:', municipalityId); // Depuración
    selectedMunicipality.value = Number(municipalityId); // Asegurar que sea número
};

const openImageModal = (image) => {
    if (image) {
        selectedImage.value = image.includes('http') ? image : `/storage/${image}`;
        showImageModal.value = true;
    }
};

const closeImageModal = () => {
    showImageModal.value = false;
    selectedImage.value = null;
};

const openDetailsModal = (classroom) => {
    selectedClassroom.value = classroom;
    showDetailsModal.value = true;
};

const closeDetailsModal = () => {
    showDetailsModal.value = false;
    selectedClassroom.value = null;
};

const truncateText = (text, maxLength = 100) => {
    if (!text) return 'N/A';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
};
</script>

<template>
    <Head title="Gestión de Aulas" />

    <AdminEstatalLayout>
        <template #header>
            <h2 class="text-2xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                Gestión de Aulas
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
                <div v-if="error" class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                    {{ error }}
                </div>
                <div v-if="Array.isArray(faculties) && faculties.length > 0" class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                        Facultad: {{ faculties[0].name }}
                    </h3>
                </div>
                
                <div class="overflow-hidden bg-white shadow-xl rounded-xl dark:bg-gray-800">
                    <div class="p-6">
                        <!-- City Selector and New Classroom Button -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
                            <Link 
                                :href="route('admin.estatal.faculties.create')" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 w-full sm:w-auto text-center"
                            >
                                Nueva Aula
                            </Link>
                        </div>

                        <!-- Loading Overlay -->
                        <div v-if="isLoading" class="absolute inset-0 bg-gray-100 bg-opacity-50 flex items-center justify-center z-10">
                            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                        </div>

                        <!-- Image Modal -->
                        <div v-if="showImageModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="closeImageModal">
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 max-w-3xl w-full mx-4" @click.stop>
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200">Imagen del Aula</h3>
                                    <button @click="closeImageModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <img 
                                    :src="selectedImage" 
                                    alt="Classroom Image" 
                                    class="w-full h-auto max-h-[70vh] object-contain rounded-lg"
                                    @error="e => e.target.src = '/images/placeholder.png'"
                                />
                            </div>
                        </div>

                        <!-- Details Modal -->
                        <div v-if="showDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="closeDetailsModal">
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto" @click.stop>
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-200">Detalles del Aula: {{ selectedClassroom.name }}</h3>
                                    <button @click="closeDetailsModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <div>
                                            <p class="text-lg"><strong>Nombre:</strong></p>
                                            <p class="text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">{{ selectedClassroom.name }}</p>
                                        </div>
                                        
                                        <div>
                                            <p class="text-lg"><strong>Capacidad:</strong></p>
                                            <p class="text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">{{ selectedClassroom.capacity }} personas</p>
                                        </div>
                                        
                                        <div>
                                            <p class="text-lg"><strong>Responsable:</strong></p>
                                            <p class="text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">{{ selectedClassroom.responsible_user?.name || 'Sin responsable asignado' }}</p>
                                        </div>
                                        
                                        <div>
                                            <p class="text-lg"><strong>Correo:</strong></p>
                                            <p class="text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                                <a v-if="selectedClassroom.email"  >
                                                    {{ selectedClassroom.email }}
                                                </a>
                                                <span v-else>Sin correo registrado</span>
                                            </p>
                                        </div>
                                        
                                        <div>
                                            <p class="text-lg"><strong>Teléfono:</strong></p>
                                            <p class="text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                                <a v-if="selectedClassroom.phone"  >
                                                    {{ selectedClassroom.phone }}
                                                </a>
                                                <span v-else>Sin teléfono registrado</span>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <p class="text-lg"><strong>Imagen:</strong></p>
                                            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                                <img 
                                                    v-if="selectedClassroom.image_url || selectedClassroom.image_path" 
                                                    :src="selectedClassroom.image_url || `/storage/${selectedClassroom.image_path}`" 
                                                    alt="Classroom Image" 
                                                    class="w-full h-auto max-h-64 object-cover rounded-lg shadow-sm cursor-pointer"
                                                    @click="openImageModal(selectedClassroom.image_url || selectedClassroom.image_path)"
                                                    @error="e => e.target.src = '/images/placeholder.png'"
                                                />
                                                <div v-else class="flex items-center justify-center h-32 bg-gray-200 dark:bg-gray-600 rounded-lg">
                                                    <span class="text-gray-500 dark:text-gray-400">Sin imagen disponible</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <p class="text-lg"><strong>Servicios:</strong></p>
                                            <div class="text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-3 rounded-lg max-h-40 overflow-y-auto">
                                                {{ selectedClassroom.services || 'Sin servicios especificados' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Responsive Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[80px]">ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[220px]">Nombre</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[220px]">Responsable</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[140px]">Capacidad</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[220px]">Servicios</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[120px]">Imagen</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[270px]">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                    <tr v-for="classroom in classrooms" :key="classroom.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">{{ classroom.id }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-200 break-words">{{ classroom.name }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-200 break-words">{{ classroom.responsible_user?.name || 'Sin responsable' }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">{{ classroom.capacity }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-200 break-words">{{ truncateText(classroom.services, 50) }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <img 
                                                v-if="classroom.image_url || classroom.image_path" 
                                                :src="classroom.image_url || `/storage/${classroom.image_path}`" 
                                                alt="Classroom Image" 
                                                class="w-10 h-10 object-cover rounded-lg shadow-sm cursor-pointer" 
                                                @click="openImageModal(classroom.image_url || classroom.image_path)"
                                                @error="e => e.target.src = '/images/placeholder.png'"
                                            />
                                            <span v-else class="text-gray-500 dark:text-gray-400">Sin imagen</span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium flex flex-col sm:flex-row gap-2">
                                            <button 
                                                @click="openDetailsModal(classroom)" 
                                                class="bg-green-600 hover:bg-green-700 text-white py-1.5 px-3 rounded-lg transition duration-200 relative group"
                                            >
                                                Detalles
                                                <span class="absolute hidden group-hover:block -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2">Ver detalles completos</span>
                                            </button>
                                            <Link 
                                                :href="route('admin.estatal.faculties.edit', classroom.id)" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white py-1.5 px-3 rounded-lg transition duration-200 relative group"
                                            >
                                                Editar
                                                <span class="absolute hidden group-hover:block -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2">Editar aula</span>
                                            </Link>
                                            <button 
                                                @click="destroy(classroom.id)" 
                                                class="bg-red-600 hover:bg-red-700 text-white py-1.5 px-3 rounded-lg transition duration-200 relative group"
                                                :disabled="isLoading"
                                            >
                                                Eliminar
                                                <span class="absolute hidden group-hover:block -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2">Eliminar aula</span>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="!classrooms || !classrooms.length">
                                        <td colspan="7" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No se encontraron aulas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Responsive Card Layout for Mobile -->
                        <div class="sm:hidden space-y-4 mt-4">
                            <div v-for="classroom in classrooms" :key="classroom.id" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg shadow">
                                <div class="grid grid-cols-1 gap-2">
                                    <div><strong>ID:</strong> {{ classroom.id }}</div>
                                    <div><strong>Nombre:</strong> {{ classroom.name }}</div>
                                    <div><strong>Responsable:</strong> {{ classroom.responsible_user?.name || 'Sin responsable' }}</div>
                                    <div><strong>Capacidad:</strong> {{ classroom.capacity }}</div>
                                    <div><strong>Servicios:</strong> {{ truncateText(classroom.services, 50) }}</div>
                                    <div><strong>Correo:</strong> {{ classroom.email || 'Sin correo' }}</div>
                                    <div><strong>Teléfono:</strong> {{ classroom.phone || 'Sin teléfono' }}</div>
                                    <div>
                                        <strong>Imagen:</strong>
                                        <img 
                                            v-if="classroom.image_url || classroom.image_path" 
                                            :src="classroom.image_url || `/storage/${classroom.image_path}`" 
                                            alt="Classroom Image" 
                                            class="w-10 h-10 object-cover rounded-lg inline-block mt-1 cursor-pointer"
                                            @click="openImageModal(classroom.image_url || classroom.image_path)"
                                            @error="e => e.target.src = '/images/placeholder.png'"
                                        />
                                        <span v-else class="text-gray-500">Sin imagen</span>
                                    </div>
                                    <div class="flex gap-2 mt-2">
                                        <button 
                                            @click="openDetailsModal(classroom)" 
                                            class="bg-green-600 hover:bg-green-700 text-white py-1.5 px-3 rounded-lg"
                                        >
                                            Detalles
                                        </button>
                                        <Link 
                                            :href="route('admin.estatal.faculties.edit', classroom.id)" 
                                            class="bg-blue-600 hover:bg-blue-700 text-white py-1.5 px-3 rounded-lg"
                                        >
                                            Editar
                                        </Link>
                                        <button 
                                            @click="destroy(classroom.id)" 
                                            class="bg-red-600 hover:bg-red-700 text-white py-1.5 px-3 rounded-lg"
                                            :disabled="isLoading"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!classrooms || !classrooms.length" class="text-center text-gray-500 dark:text-gray-400 p-4">
                                No se encontraron aulas
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminEstatalLayout>
</template>

<style scoped>
/* Ensure text wraps properly */
.break-words {
    word-break: break-word;
}

/* Full-width table adjustments */
table {
    width: 100%;
    table-layout: auto;
}

/* Modal transition */
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>