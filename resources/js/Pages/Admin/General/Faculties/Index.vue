<script setup>
import AdminGeneralLayout from '@/Layouts/AdminGeneralLayout.vue';
import CityButtonGroup from '@/Components/CityButtonGroup.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    faculties: Array,
    municipalities: Array,
    selectedMunicipality: Number,
});

const isLoading = ref(false);
const showImageModal = ref(false);
const selectedImage = ref(null);
const showDetailsModal = ref(false);
const selectedFaculty = ref(null);

// Variable reactiva para el municipio seleccionado
const selectedMunicipality = ref(props.selectedMunicipality || 1);

// Propiedad computada para filtrar facultades
const filteredFaculties = computed(() => {
    const result = props.faculties.filter(faculty => {
        // Convertimos municipality_id a número para evitar problemas de tipo
        return Number(faculty.municipality_id) === Number(selectedMunicipality.value);
    });
    // Depuración: Mostrar cuántas facultades se encontraron
    console.log('Filtered Faculties:', result);
    console.log('Selected Municipality ID:', selectedMunicipality.value);
    return result;
});

// Depuración inicial de los datos recibidos
console.log('Props Faculties:', props.faculties);
console.log('Props Municipalities:', props.municipalities);
console.log('Initial Selected Municipality:', selectedMunicipality.value);

// Observar cambios en selectedMunicipality para depurar
watch(selectedMunicipality, (newValue) => {
    console.log('Selected Municipality Changed:', newValue);
});

// Manejar la selección de municipio
const selectCity = (municipalityId) => {
    console.log('City Selected:', municipalityId); // Depuración
    selectedMunicipality.value = Number(municipalityId); // Asegurar que sea número
};

const destroy = (id) => {
    if (confirm('¿Estás seguro de que quieres eliminar esta facultad y sus aulas?')) {
        isLoading.value = true;
        router.delete(route('admin.general.faculties.destroy', id), {
            onFinish: () => { isLoading.value = false; }
        });
    }
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

const openDetailsModal = (faculty) => {
    selectedFaculty.value = faculty;
    showDetailsModal.value = true;
};

const closeDetailsModal = () => {
    showDetailsModal.value = false;
    selectedFaculty.value = null;
};

const truncateText = (text, maxLength = 100) => {
    if (!text) return 'N/A';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
};
</script>

<template>
    <Head title="Facultades" />

    <AdminGeneralLayout>
        <template #header>
            <h2 class="text-2xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                Gestión de Facultades
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-xl rounded-xl dark:bg-gray-800">
                    <div class="p-6">
                        <!-- City Selector and New Faculty Button -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
                            <CityButtonGroup 
                                :cities="municipalities" 
                                :selected-city="selectedMunicipality" 
                                @city-selected="selectCity" 
                                class="w-full sm:w-auto"
                            />
                            <Link 
                                :href="route('admin.general.faculties.create')" 
                                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 w-full sm:w-auto text-center"
                            >
                                Nueva Facultad
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
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-200">Imagen de la Facultad</h3>
                                    <button @click="closeImageModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <img 
                                    :src="selectedImage" 
                                    alt="Faculty Image" 
                                    class="w-full h-auto max-h-[70vh] object-contain rounded-lg"
                                    @error="e => e.target.src = '/images/placeholder.png'"
                                />
                            </div>
                        </div>

                        <!-- Details Modal -->
                        <div v-if="showDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="closeDetailsModal">
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto" @click.stop>
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-200">Detalles de la Facultad: {{ selectedFaculty.name }}</h3>
                                    <button @click="closeDetailsModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <p><strong>ID:</strong> {{ selectedFaculty.id }}</p>
                                        <p><strong>Ubicación:</strong> {{ selectedFaculty.location }}</p>
                                        <p><strong>Responsable:</strong> {{ selectedFaculty.responsible_user?.name || 'Sin responsable' }}</p>
                                        <p><strong>Sitio Web:</strong> 
                                            <a v-if="selectedFaculty.web_site" :href="selectedFaculty.web_site" target="_blank" class="text-blue-600 hover:text-blue-800">Visitar</a>
                                            <span v-else>N/A</span>
                                        </p>
                                        <p><strong>Imagen:</strong></p>
                                        <img 
                                            v-if="selectedFaculty.image" 
                                            :src="selectedFaculty.image.includes('http') ? selectedFaculty.image : `/storage/${selectedFaculty.image}`" 
                                            alt="Faculty Image" 
                                            class="w-full h-auto max-h-64 object-cover rounded-lg shadow-sm cursor-pointer mt-2"
                                            @click="openImageModal(selectedFaculty.image)"
                                            @error="e => e.target.src = '/images/placeholder.png'"
                                        />
                                        <span v-else class="text-gray-500 dark:text-gray-400">Sin imagen</span>
                                    </div>
                                    <div>
                                        <p><strong>Servicios:</strong> {{ selectedFaculty.services || 'N/A' }}</p>
                                        <p class="mt-4"><strong>Aulas:</strong></p>
                                        <ul class="list-disc list-inside mt-2">
                                            <li v-for="classroom in selectedFaculty.classrooms" :key="classroom.id" class="break-words">
                                                {{ classroom.name }} (Cap: {{ classroom.capacity }})
                                            </li>
                                            <li v-if="!selectedFaculty.classrooms.length" class="text-gray-500 dark:text-gray-400">
                                                Sin aulas
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="mt-6 text-center">
                                    <p><strong>Descripción:</strong> {{ selectedFaculty.description || 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Responsive Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[80px]">ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[200px]">Nombre</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[200px]">Ubicación</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[200px]">Responsable</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[150px]">Servicios</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[150px]">Descripción</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[100px]">Imagen</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[100px]">Aulas</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-[250px]">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                    <tr v-for="faculty in filteredFaculties" :key="faculty.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">{{ faculty.id }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-200 break-words">{{ faculty.name }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-200 break-words">{{ truncateText(faculty.location, 50) }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-200 break-words">{{ faculty.responsible_user?.name || 'Sin responsable' }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-200 break-words">{{ truncateText(faculty.services, 50) }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-200 break-words">{{ truncateText(faculty.description, 50) }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <img 
                                                v-if="faculty.image" 
                                                :src="faculty.image.includes('http') ? faculty.image : `/storage/${faculty.image}`" 
                                                alt="Faculty Image" 
                                                class="w-10 h-10 object-cover rounded-lg shadow-sm cursor-pointer" 
                                                @click="openImageModal(faculty.image)"
                                                @error="e => e.target.src = '/images/placeholder.png'"
                                            />
                                            <span v-else class="text-gray-500 dark:text-gray-400">Sin imagen</span>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-200">{{ faculty.classrooms.length }} aulas</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium flex flex-col sm:flex-row gap-2">
                                            <button 
                                                @click="openDetailsModal(faculty)" 
                                                class="bg-green-600 hover:bg-green-700 text-white py-1.5 px-3 rounded-lg transition duration-200 relative group"
                                            >
                                                Detalles
                                                <span class="absolute hidden group-hover:block -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2">Ver detalles completos</span>
                                            </button>
                                            <Link 
                                                :href="route('admin.general.faculties.edit', faculty.id)" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white py-1.5 px-3 rounded-lg transition duration-200 relative group"
                                            >
                                                Editar
                                                <span class="absolute hidden group-hover:block -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2">Editar facultad</span>
                                            </Link>
                                            <button 
                                                @click="destroy(faculty.id)" 
                                                class="bg-red-600 hover:bg-red-700 text-white py-1.5 px-3 rounded-lg transition duration-200 relative group"
                                                :disabled="isLoading"
                                            >
                                                Eliminar
                                                <span class="absolute hidden group-hover:block -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs rounded py-1 px-2">Eliminar facultad</span>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="!filteredFaculties.length">
                                        <td colspan="9" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No se encontraron facultades para el municipio seleccionado
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Responsive Card Layout for Mobile -->
                        <div class="sm:hidden space-y-4 mt-4">
                            <div v-for="faculty in filteredFaculties" :key="faculty.id" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg shadow">
                                <div class="grid grid-cols-1 gap-2">
                                    <div><strong>ID:</strong> {{ faculty.id }}</div>
                                    <div><strong>Nombre:</strong> {{ faculty.name }}</div>
                                    <div><strong>Ubicación:</strong> {{ truncateText(faculty.location, 50) }}</div>
                                    <div><strong>Responsable:</strong> {{ faculty.responsible_user?.name || 'Sin responsable' }}</div>
                                    <div><strong>Servicios:</strong> {{ truncateText(faculty.services, 50) }}</div>
                                    <div><strong>Descripción:</strong> {{ truncateText(faculty.description, 50) }}</div>
                                    <div>
                                        <strong>Sitio Web:</strong>
                                        <a v-if="faculty.web_site" :href="faculty.web_site" target="_blank" class="text-blue-600">Visitar</a>
                                        <span v-else>N/A</span>
                                    </div>
                                    <div>
                                        <strong>Imagen:</strong>
                                        <img 
                                            v-if="faculty.image" 
                                            :src="faculty.image.includes('http') ? faculty.image : `/storage/${faculty.image}`" 
                                            alt="Faculty Image" 
                                            class="w-10 h-10 object-cover rounded-lg inline-block mt-1 cursor-pointer"
                                            @click="openImageModal(faculty.image)"
                                            @error="e => e.target.src = '/images/placeholder.png'"
                                        />
                                        <span v-else class="text-gray-500">Sin imagen</span>
                                    </div>
                                    <div><strong>Aulas:</strong> {{ faculty.classrooms.length }} aulas</div>
                                    <div class="flex gap-2 mt-2">
                                        <button 
                                            @click="openDetailsModal(faculty)" 
                                            class="bg-green-600 hover:bg-green-700 text-white py-1.5 px-3 rounded-lg"
                                        >
                                            Detalles
                                        </button>
                                        <Link 
                                            :href="route('admin.general.faculties.edit', faculty.id)" 
                                            class="bg-blue-600 hover:bg-blue-700 text-white py-1.5 px-3 rounded-lg"
                                        >
                                            Editar
                                        </Link>
                                        <button 
                                            @click="destroy(faculty.id)" 
                                            class="bg-red-600 hover:bg-red-700 text-white py-1.5 px-3 rounded-lg"
                                            :disabled="isLoading"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!filteredFaculties.length" class="text-center text-gray-500 dark:text-gray-400 p-4">
                                No se encontraron facultades para el municipio seleccionado
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminGeneralLayout>
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