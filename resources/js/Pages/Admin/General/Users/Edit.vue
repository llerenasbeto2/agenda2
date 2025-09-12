<script setup>
import AdminGeneralLayout from '@/Layouts/AdminGeneralLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    user: Object,
    roles: Array,
    faculties: Array,
    classrooms: Array,
    municipalities: Array
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    municipality_id: props.user.municipality_id,
    rol_id: props.user.rol_id,
    responsible_id: props.user.responsible_id
});

const responsibleOptions = ref([]);

watch([() => form.rol_id, () => form.municipality_id], ([newRolId, newMunicipalityId]) => {
    console.log('Watch triggered:', { newRolId, newMunicipalityId, roles: props.roles, classrooms: props.classrooms });
    const rol = props.roles.find(r => r.id === parseInt(newRolId));
    if (!rol || !newMunicipalityId) {
        console.log('No rol or no municipality selected, clearing responsibleOptions');
        responsibleOptions.value = [];
        form.responsible_id = '';
        return;
    }

    console.log('Selected rol:', rol.name);
    if (rol.name === 'Administrador estatal') {
        const filteredFaculties = props.faculties.filter(f => f.municipality_id === parseInt(newMunicipalityId));
        responsibleOptions.value = filteredFaculties.map(f => ({ id: f.id, name: f.name }));
        console.log('Filtered faculties:', filteredFaculties);
    } else if (rol.name === 'Administrador area') {
        const filteredClassrooms = props.classrooms.filter(c => c.municipality_id === parseInt(newMunicipalityId));
        responsibleOptions.value = filteredClassrooms.map(c => ({ id: c.id, name: c.name }));
        console.log('Filtered classrooms:', filteredClassrooms);
    } else {
        console.log('No responsible options for this role');
        responsibleOptions.value = [];
        form.responsible_id = '';
    }
}, { immediate: true });

function submit() {
    form.put(route('admin.general.usuarios.update', props.user.id), {
        onSuccess: () => {
            // Success handling
        }
    });
}
</script>

<template>
    <Head title="Editar Usuario" />
    <AdminGeneralLayout>
        <div class="container mx-auto py-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Editar Usuario</h1>
                <Link :href="route('admin.general.usuarios.index')" class="bg-gray-500 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-md transition">
                    Volver
                </Link>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nombre</label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Email</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                        </div>

                        <div>
                            <label for="municipality_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Municipio</label>
                            <select
                                id="municipality_id"
                                v-model="form.municipality_id"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="" disabled>Selecciona un municipio</option>
                                <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">{{ municipality.name }}</option>
                            </select>
                            <div v-if="form.errors.municipality_id" class="text-red-500 text-sm mt-1">{{ form.errors.municipality_id }}</div>
                        </div>

                        <div>
                            <label for="rol_id" class="block textsm font-medium text-gray-700 dark:text-gray-200">Rol</label>
                            <select
                                id="rol_id"
                                v-model="form.rol_id"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="" disabled>Selecciona un rol</option>
                                <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                            </select>
                            <div v-if="form.errors.rol_id" class="text-red-500 text-sm mt-1">{{ form.errors.rol_id }}</div>
                        </div>

                        <div v-if="responsibleOptions.length > 0">
                            <label for="responsible_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Responsable</label>
                            <select
                                id="responsible_id"
                                v-model="form.responsible_id"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="" disabled>Selecciona un responsable</option>
                                <option v-for="option in responsibleOptions" :key="option.id" :value="option.id">{{ option.name }}</option>
                            </select>
                            <div v-if="form.errors.responsible_id" class="text-red-500 text-sm mt-1">{{ form.errors.responsible_id }}</div>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 dark:hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Actualizando...' : 'Actualizar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminGeneralLayout>
</template>