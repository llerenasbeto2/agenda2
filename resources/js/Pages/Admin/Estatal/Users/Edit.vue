<script setup>
import AdminEstatalLayout from '@/Layouts/AdminEstatalLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    user: Object,
    roles: Array,
    municipalities: Array,
    classrooms: Array
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    municipality_id: props.user.municipality_id,
    rol_id: props.user.rol_id,
    responsible_id: props.user.responsible_id
});

// Filtrar aulas según el municipio seleccionado
const filteredClassrooms = computed(() => {
    if (!form.municipality_id) return [];
    return props.classrooms.filter(classroom => classroom.municipality_id === form.municipality_id);
});

// Mostrar campo de responsable solo si el rol no es "Usuario"
const showResponsible = computed(() => {
    const usuarioRole = props.roles.find(role => role.name === 'Usuario');
    return form.rol_id !== usuarioRole?.id;
});

// Limpiar responsible_id si el rol es "Usuario"
watch(() => form.rol_id, (newRolId) => {
    const usuarioRole = props.roles.find(role => role.name === 'Usuario');
    if (newRolId === usuarioRole?.id) {
        form.responsible_id = null;
    }
});

function submit() {
    form.put(route('admin.estatal.usuarios.update', props.user.id), {
        onSuccess: () => {
            form.reset();
        }
    });
}
</script>

<template>
    <AdminEstatalLayout>
        <div class="container mx-auto py-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Editar Usuario</h1>
                <Link :href="route('admin.estatal.usuarios.index')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Volver
                </Link>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form @submit.prevent="submit">
                        <!-- Nombre -->
                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nombre:</label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                required
                            />
                            <div v-if="form.errors.name" class="text-red-500 mt-1">{{ form.errors.name }}</div>
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                required
                            />
                            <div v-if="form.errors.email" class="text-red-500 mt-1">{{ form.errors.email }}</div>
                        </div>

                        <!-- Municipio -->
                        <div class="mb-4">
                            <label for="municipality_id" class="block text-gray-700 text-sm font-bold mb-2">Municipio:</label>
                            <select
                                id="municipality_id"
                                v-model="form.municipality_id"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                required
                            >
                                <option value="" disabled>Selecciona un municipio</option>
                                <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
                                    {{ municipality.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.municipality_id" class="text-red-500 mt-1">{{ form.errors.municipality_id }}</div>
                        </div>

                        <!-- Rol -->
                        <div class="mb-4">
                            <label for="rol_id" class="block text-gray-700 text-sm font-bold mb-2">Rol:</label>
                            <select
                                id="rol_id"
                                v-model="form.rol_id"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                required
                            >
                                <option value="" disabled>Selecciona un rol</option>
                                <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                            </select>
                            <div v-if="form.errors.rol_id" class="text-red-500 mt-1">{{ form.errors.rol_id }}</div>
                        </div>

                        <!-- Responsable (Aula) -->
                        <div v-if="showResponsible" class="mb-4">
                            <label for="responsible_id" class="block text-gray-700 text-sm font-bold mb-2">Responsable (Aula):</label>
                            <select
                                id="responsible_id"
                                v-model="form.responsible_id"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            >
                                <option value="" disabled>Selecciona un aula</option>
                                <option v-for="classroom in filteredClassrooms" :key="classroom.id" :value="classroom.id">
                                    {{ classroom.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.responsible_id" class="text-red-500 mt-1">{{ form.errors.responsible_id }}</div>
                        </div>

                        <!-- Botón de enviar -->
                        <div class="flex items-center justify-end">
                            <button
                                type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Actualizando...' : 'Actualizar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminEstatalLayout>
</template>