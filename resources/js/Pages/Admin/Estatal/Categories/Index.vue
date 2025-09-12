<script setup>

import AdminEstatalLayout from '@/Layouts/AdminEstatalLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
// Define tus datos y métodos aquí
const props = defineProps({
    categories: Array
});

const destroy = (id) => {
    if (confirm('¿Estás seguro de que quieres eliminar esta categoría?')) {
        router.delete(route('admin.estatal.categories.destroy', id));
    }
};


const page = usePage();

const isEstado = computed(() => {
    const user = page.props.auth.user;
    return user?.municipality; // Devuelve el ID del municipio
});

const filtered = computed(() => 
    props.categories.filter(stat => {
        // Solo incluir posts del mismo municipio que el usuario
        return stat.municipality_id === isEstado.value;
    })
);
</script>

<template>
    <Head title="Mis Categorías" />

    <AdminEstatalLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Mis Categorías
            </h2>
        </template>
       
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
                        
                        <Link :href="route('admin.estatal.categories.create')" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mb-4 inline-block">Nueva Categoría</Link>
                        <table class="min-w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden mt-4">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-600 border-b dark:border-gray-500">
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">ID</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Nombre</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="category in filtered" :key="category.id" class="border-b dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ category.id }}</td>
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ category.name }}</td>
                                    <td class="py-2 px-4 flex gap-2">
                                        <Link :href="route('admin.estatal.categories.edit', category.id)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded">Editar</Link>
                                        <button @click="destroy(category.id)" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded">Eliminar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminEstatalLayout>
</template>