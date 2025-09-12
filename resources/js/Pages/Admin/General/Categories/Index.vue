<script setup>
import AdminGeneralLayout from '@/Layouts/AdminGeneralLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

// Define props
const props = defineProps({
    categories: Array,
    municipalities: Array // Nuevo prop para recibir municipios
});

// Estado para el municipio seleccionado
const selectedMunicipality = ref('all');

// Filtrar categorías basado en el municipio seleccionado
const filteredCategories = computed(() => {
    if (selectedMunicipality.value === 'all') {
        return props.categories;
    } else {
        return props.categories.filter(category => 
            category.municipality_id == selectedMunicipality.value
        );
    }
});

// Método para eliminar una categoría
const destroy = (id) => {
    if (confirm('¿Estás seguro de que quieres eliminar esta categoría?')) {
        router.delete(route('admin.general.categories.destroy', id));
    }
};
</script>

<template>
    <Head title="Categorias" />

    <AdminGeneralLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Categorias
            </h2>
        </template>
       
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            <Link :href="route('admin.general.categories.create')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-block">Nueva Categoría</Link>
                            
                            <!-- Selector de municipio -->
                            <div class="flex items-center">
                                <label for="municipality-filter" class="mr-2 text-gray-700 dark:text-gray-200">Filtrar por municipio:</label>
                                <select 
                                    id="municipality-filter" 
                                    v-model="selectedMunicipality"
                                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200"
                                >
                                    <option value="all">Todos los municipios</option>
                                    <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
                                        {{ municipality.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <table class="min-w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden mt-4">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-600 border-b dark:border-gray-500">
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">ID</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Nombre</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Municipio</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="category in filteredCategories" :key="category.id" class="border-b dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ category.id }}</td>
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ category.name }}</td>
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">
                                        {{ municipalities.find(m => m.id === category.municipality_id)?.name || 'No asignado' }}
                                    </td>
                                    <td class="py-2 px-4 flex gap-2">
                                        <Link :href="route('admin.general.categories.edit', category.id)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded">Editar</Link>
                                        <button @click="destroy(category.id)" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="filteredCategories.length === 0" class="border-b dark:border-gray-600">
                                    <td colspan="4" class="py-4 px-4 text-center text-gray-800 dark:text-gray-200">
                                        No hay categorías para mostrar en este municipio
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminGeneralLayout>
</template>