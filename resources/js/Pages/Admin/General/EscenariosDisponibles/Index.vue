<script setup>
import AdminGeneralLayout from '@/Layouts/AdminGeneralLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Scrollbar from '@/Components/Scrollbar.vue';

defineProps({
    posts: {
        type: Array,
        required: true
    },
});
</script>

<template>
    <Head title="Escenarios Disponibles" />

    <AdminGeneralLayout>
        <template #header>
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white text-center">
                Escenarios Disponibles
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="mb-6 flex justify-end">
                            <Link 
                                :href="route('admin.general.escenariosDisponibles.create')"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-500 hover:bg-indigo-700 dark:hover:bg-indigo-600 text-white font-semibold rounded-md transition-colors duration-200"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Crear Escenario
                            </Link>
                        </div>

                        <Scrollbar max-height="600px">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ubicación</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Capacidad</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Servicios</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Descripción</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Responsable</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Teléfono</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Imagen</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Municipio</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="post in posts" 
                                        :key="post.id" 
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                                    >
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ post.id }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ post.name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 max-w-xs truncate">{{ post.location }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ post.capacity }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 max-w-xs truncate">{{ post.services }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 max-w-xs truncate">{{ post.description }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ post.responsible }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ post.email }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ post.phone }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100 max-w-xs truncate">
                                            <a :href="post.image" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline">Ver</a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ post.municipality ? post.municipality.name : 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="flex space-x-2">
                                                <Link 
                                                    :href="route('admin.general.escenariosDisponibles.edit', post.id)"
                                                    class="inline-flex items-center px-3 py-1 bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 text-white text-sm font-medium rounded-md transition-colors duration-200"
                                                >
                                                    Editar
                                                </Link>
                                                <Link 
                                                    :href="route('admin.general.escenariosDisponibles.destroy', post.id)"
                                                    method="delete"
                                                    as="button"
                                                    class="inline-flex items-center px-3 py-1 bg-red-600 dark:bg-red-500 hover:bg-red-700 dark:hover:bg-red-600 text-white text-sm font-medium rounded-md transition-colors duration-200"
                                                    :preserve-scroll="true"
                                                >
                                                    Eliminar
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </Scrollbar>
                    </div>
                </div>
            </div>
        </div>
    </AdminGeneralLayout>
</template>