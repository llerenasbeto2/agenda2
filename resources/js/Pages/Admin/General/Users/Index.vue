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

// Inicializar filtros con valores por defecto
const filters = ref({
    name: props.filters?.name || '',
    rol_filter: props.filters?.rol_filter || ''
});

const search = () => {
    console.log('Search triggered with filters:', filters.value);
    
    // Crear objeto de parámetros solo con valores no vacíos
    const params = {};
    if (filters.value.name) params.name = filters.value.name;
    if (filters.value.rol_filter) params.rol_filter = filters.value.rol_filter;
    
    router.get(
        route('admin.general.usuarios.index'),
        params,
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

// Debounce para el filtro de nombre
let searchTimeout = null;
watch(() => filters.value.name, (newValue) => {
    console.log('Name filter changed:', newValue);
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 300);
});

// Búsqueda inmediata para el filtro de rol
watch(() => filters.value.rol_filter, () => {
    console.log('Role filter changed:', filters.value.rol_filter);
    search();
});

const clearFilters = () => {
    filters.value.name = '';
    filters.value.rol_filter = '';
    search();
};

const getRoleColorClasses = (rolName) => {
    switch (rolName) {
        case 'Admin general':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
        case 'Administrador estatal':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        case 'Administrador area':
        case 'Administrador de área':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'Usuario':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
};
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
                                
                                <!-- Filtro por rol -->
                                <div class="flex items-center">
                                    <select 
                                        v-model="filters.rol_filter"
                                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 min-w-[180px]"
                                    >
                                        <option value="">Todos los roles</option>
                                        <option value="4">Admin general</option>
                                        <option value="3">Administrador estatal</option>
                                        <option value="2">Administrador de área</option>
                                        <option value="1">Usuario</option>
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
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Rol</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Responsable</th>
                                    <th class="py-2 px-4 text-left text-gray-700 dark:text-gray-200">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users" :key="user.id" class="border-b dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ user.name }}</td>
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">{{ user.email }}</td>
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">
                                        <span 
                                            :class="getRoleColorClasses(user.rol ? user.rol.name : null)"
                                            class="px-2 py-1 rounded-full text-xs font-semibold"
                                        >
                                            {{ user.rol ? user.rol.name : 'Sin rol' }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-4 text-gray-800 dark:text-gray-200">
                                        <span v-if="user.rol && user.responsible_id">
                                            <span v-if="user.rol.name === 'Administrador estatal'">{{ user.responsibleFaculty ? user.responsibleFaculty.name : 'Facultad no encontrada' }}</span>
                                            <span v-else-if="user.rol.name === 'Administrador area' || user.rol.name === 'Administrador de área'">{{ user.responsibleClassroom ? user.responsibleClassroom.name : 'Aula no encontrada' }}</span>
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
    </AdminGeneralLayout>
</template>