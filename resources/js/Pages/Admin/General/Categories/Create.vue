<script setup>
import AdminGeneralLayout from '@/Layouts/AdminGeneralLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    municipalities: Array // Nuevo prop para recibir municipios
});

const form = useForm({
    name: '',
    municipality_id: '' // Nuevo campo para el municipio
});

const submit = () => {
    form.post(route('admin.general.categories.store'));
};
</script>

<template>
    <Head title="Crear Categoría" />

    <AdminGeneralLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Crear Categoría
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <label for="name" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    v-model="form.name" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    required
                                >
                                <div v-if="form.errors.name" class="text-red-500 mt-1 text-sm">{{ form.errors.name }}</div>
                            </div>
                            
                            <!-- Selector de municipio -->
                            <div class="mb-4">
                                <label for="municipality" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Municipio</label>
                                <select 
                                    id="municipality" 
                                    v-model="form.municipality_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    required
                                >
                                    <option value="" disabled>Seleccione un municipio</option>
                                    <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
                                        {{ municipality.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.municipality_id" class="text-red-500 mt-1 text-sm">{{ form.errors.municipality_id }}</div>
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <button 
                                    type="submit" 
                                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:bg-blue-600"
                                    :disabled="form.processing"
                                >
                                    Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminGeneralLayout>
</template>