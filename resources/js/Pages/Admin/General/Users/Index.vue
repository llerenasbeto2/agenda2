<script setup>
import AdminGeneralLayout from '@/Layouts/AdminGeneralLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    users: Array,
    filters: Object
});

const destroy = (id) => {
    if (confirm('¿Estás seguro de que quieres eliminar este usuario?')) {
        router.delete(route('admin.general.usuarios.destroy', id));
    }
};

const filters = ref(props.filters || { name: '' });

const search = () => {
    console.log('Search triggered with filters:', filters.value);
    router.get(
        route('admin.general.usuarios.index'),
        { name: filters.value.name },
        {
            preserveState: true,
            replace: true,
            onSuccess: () => {
                console.log('Search request successful');
            },
            onError: (errors) => {
                console.log('Search request failed:', errors);
            }
        }
    );
};

let searchTimeout = null;
watch(() => filters.value.name, (newValue) => {
    console.log('Name filter changed:', newValue);
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 10);
});
</script>

<template>
    <Head title="Usuarios" />

    <AdminGeneralLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Usuarios
            </h2>
        </template>
       
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
                        <div class="flex justify-between items-center mb-4">
                            
                            
                            <!-- Buscador -->
                            <div class="flex items-center">
                                <form @submit.prevent="search" class="flex items-center space-x-2">
                                    <input 
                                        v-model="filters.name" 
                                        name="name"
                                        placeholder="Buscar por nombre" 
                                        type="text"
                                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200"
                                    />
                                    <button 
                                        type="submit" 
                                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition"
                                    >
                                        Buscar
                                    </button>
                                </form>
                            </div>
                        </div>

                        <table class="min-w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden mt-4">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-600 border-b dark:border-gray-500">
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Nombre</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Email</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Rol</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Responsable</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users" :key="user.id" class="border-b dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ user.name }}</td>
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ user.email }}</td>
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ user.rol ? user.rol.name : 'Sin rol' }}</td>
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">
                                        <span v-if="user.rol && user.responsible_id">
                                            <span v-if="user.rol.name === 'Administrador estatal'">{{ user.responsibleFaculty ? user.responsibleFaculty.name : 'Facultad no encontrada' }}</span>
                                            <span v-else-if="user.rol.name === 'Administrador area'">{{ user.responsibleClassroom ? user.responsibleClassroom.name : 'Aula no encontrada' }}</span>
                                            <span v-else>-</span>
                                        </span>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="py-2 px-4 flex gap-2">
                                        <Link :href="route('admin.general.usuarios.edit', user.id)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded">Editar</Link>
                                        <button @click="destroy(user.id)" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0" class="border-b dark:border-gray-600">
                                    <td colspan="5" class="py-4 px-4 text-center text-gray-800 dark:text-gray-200">
                                        No hay usuarios para mostrar
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