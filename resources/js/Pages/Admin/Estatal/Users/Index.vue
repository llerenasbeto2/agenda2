<script setup>
import AdminEstatalLayout from '@/Layouts/AdminEstatalLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    users: Array,
    roles: Array,
    filters: Object
});

const destroy = (id) => {
    if (confirm('¿Estás seguro de que quieres eliminar este usuario?')) {
        router.delete(route('admin.estatal.usuarios.destroy', id));
    }
};

const filters = ref(props.filters || { name: '', rol_filter: '' });

const search = () => {
    console.log('Search triggered with filters:', filters.value);
    router.get(
        route('admin.estatal.usuarios.index'),
        { 
            name: filters.value.name,
            rol_filter: filters.value.rol_filter
        },
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
    }, 300);
});

watch(() => filters.value.rol_filter, (newValue) => {
    console.log('Role filter changed:', newValue);
    search();
});

const clearFilters = () => {
    filters.value.name = '';
    filters.value.rol_filter = '';
    search();
};

const getRoleName = (rolId) => {
    switch (rolId) {
        case 1:
            return 'Usuario';
        case 2:
            return 'Administrador de Área';
        default:
            return 'Sin rol';
    }
};
</script>

<template>
    <Head title="Usuarios" />

    <AdminEstatalLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Usuarios
            </h2>
        </template>
       
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                            
                            <!-- Filtros -->
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full">
                                <!-- Buscador por nombre -->
                                <div class="flex items-center">
                                    <input 
                                        v-model="filters.name" 
                                        name="name"
                                        placeholder="Buscar por nombre" 
                                        type="text"
                                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 min-w-[200px]"
                                    />
                                </div>
                                
                                <!-- Filtro por tipo de rol -->
                                <div class="flex items-center">
                                    <select 

                                        placeholder="seleccione una opcion"
                                        v-model="filters.rol_filter"
                                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 min-w-[180px]"
                                    >
                                        <option value="">Todos los tipos</option>
                                        <option value="1">Usuarios</option>
                                        <option value="2">Administradores de Área</option>
                                    </select>
                                </div>
                                
                                <!-- Botón limpiar filtros -->
                                <button 
                                    @click="clearFilters" 
                                    class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded transition"
                                    v-if="filters.name || filters.rol_filter"
                                >
                                    Limpiar
                                </button>
                            </div>
                        </div>

                        

                        <table class="min-w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden mt-4">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-600 border-b dark:border-gray-500">
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Nombre</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Email</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Tipo</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Responsable</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Municipio</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users" :key="user.id" class="border-b dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ user.name }}</td>
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ user.email }}</td>
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">
                                        <span :class="{
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': user.rol_id === 1,
                                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': user.rol_id === 2
                                        }" class="px-2 py-1 rounded-full text-xs font-semibold">
                                            {{ getRoleName(user.rol_id) }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ user.responsible || '-' }}</td>
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ user.municipality || '-' }}</td>
                                    <td class="py-2 px-4 flex gap-2">
                                        <Link :href="route('admin.estatal.usuarios.edit', user.id)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded">Editar</Link>
                                        <button @click="destroy(user.id)" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded">Eliminar</button>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0" class="border-b dark:border-gray-600">
                                    <td colspan="6" class="py-4 px-4 text-center text-gray-800 dark:text-gray-200">
                                        No hay usuarios para mostrar
                                        <span v-if="filters.name || filters.rol_filter" class="block text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            Intenta cambiar los filtros de búsqueda
                                        </span>
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