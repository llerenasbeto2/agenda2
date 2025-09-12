<script setup>
import AdminEstatalLayout from '@/Layouts/AdminEstatalLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    users: Array,
    filters: Object
});

const destroy = (id) => {
    if (confirm('¿Estás seguro de que quieres eliminar este usuario?')) {
        router.delete(route('admin.estatal.usuarios.destroy', id));
    }
};

const filters = ref(props.filters || { name: '' });

const search = () => {
    router.get(route('admin.estatal.usuarios.index'), {
        name: filters.value.name
    }, {
        preserveState: true,
        replace: true
    });
};

let searchTimeout = null;

watch(() => filters.value.name, (newValue) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 300);
});
</script>

<template>
  <AdminEstatalLayout>
    <div class="container mx-auto py-8">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Usuarios</h1>
        
        <form method="get" :action="route('admin.estatal.usuarios.index')" @submit.prevent="search">
          <div class="flex items-center">
            <input 
              v-model="filters.name" 
              name="name"
              placeholder="Buscar por nombre" 
              type="text"
              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            />
            <button 
              type="submit" 
              class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded ml-2"
            >
              Buscar
            </button>
          </div>
        </form>
      </div>

      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
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
                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-200">{{ user.rol }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-200">{{ user.responsible }}</td>
                <td class="px-6 py-4 whitespace-nowrap flex">
                  <Link :href="route('admin.estatal.usuarios.edit', user.id)" class="mr-2 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
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
  </AdminEstatalLayout>
</template>