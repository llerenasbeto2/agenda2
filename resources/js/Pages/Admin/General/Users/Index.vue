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
    <div class="container mx-auto py-8">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Usuarios</h1>
        
        <form @submit.prevent="search" class="flex items-center space-x-2">
          <input 
            v-model="filters.name" 
            name="name"
            placeholder="Buscar por nombre" 
            type="text"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
          <button 
            type="submit" 
            class="bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white font-semibold py-2 px-4 rounded-md transition"
          >
            Buscar
          </button>
        </form>
      </div>

      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rol</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Responsable</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Opciones</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-200">{{ user.name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-200">{{ user.email }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-200">{{ user.rol ? user.rol.name : 'Sin rol' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-200">
                  <span v-if="user.rol && user.responsible_id">
                    <span v-if="user.rol.name === 'Administrador estatal'">{{ user.responsibleFaculty ? user.responsibleFaculty.name : 'Facultad no encontrada' }}</span>
                    <span v-else-if="user.rol.name === 'Administrador area'">{{ user.responsibleClassroom ? user.responsibleClassroom.name : 'Aula no encontrada' }}</span>
                    <span v-else>-</span>
                  </span>
                  <span v-else>-</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap flex space-x-2">
                  <Link :href="route('admin.general.usuarios.edit', user.id)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                    Editar
                  </Link>
                  <button @click="destroy(user.id)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                    Eliminar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminGeneralLayout>
</template>