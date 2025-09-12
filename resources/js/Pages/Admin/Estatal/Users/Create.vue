<script setup>
import AdminEstatalLayout from '@/Layouts/AdminEstatalLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

// Recibimos los roles como prop
const props = defineProps({
    roles: Array
});

const form = useForm({
    name: '',
    email: '',
    password: 'password_default', // Necesitamos esto de vuelta
    rol_id: '',
   
});

function submit() {
    form.post(route('admin.estatal.usuarios.store'), {
        onSuccess: () => {
            form.reset();
            // Restauramos el valor predeterminado de la contraseña después del reset
            form.password = 'password_default';
        },
    });
}
</script>

<template>
    <Head title="Crear Usuario" />

    <AdminEstatalLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Crear Nuevo Usuario
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Nombre
                                </label>
                                <input 
                                    id="name"
                                    type="text"
                                    v-model="form.name"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Email
                                </label>
                                <input 
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600"
                                    required
                                />
                                <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <div>
                                <label for="rol_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Rol
                                </label>
                                <select
                                    id="rol_id"
                                    v-model="form.rol_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600"
                                    required
                                >
                                    <option value="" disabled>Selecciona un rol</option>
                                    <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                                </select>
                                <div v-if="form.errors.rol_id" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.rol_id }}
                                </div>
                            </div>

                            

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    :disabled="form.processing"
                                >
                                    {{ form.processing ? 'Guardando...' : 'Guardar Usuario' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminEstatalLayout>
</template>