<script setup>
import AdminEstatalLayout from '@/Layouts/AdminEstatalLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    faculty: Object,
    municipalities: Array,
    users: Array,
});

const form = useForm({
    name: '',
    capacity: '',
    services: '',
    responsible: '',
    email: '',
    phone: '',
    image_option: 'url',
    image_url: '',
    image_file: null,
    uses_db_storage: false,
});

const classroomUsers = ref(props.users.filter(u => u.rol_id === 2));

const handleImageFile = (event) => {
    if (event.target.files && event.target.files[0]) {
        form.image_file = event.target.files[0];
    }
};

const submit = () => {
    form.post(route('admin.estatal.faculties.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Crear Aula" />

    <AdminEstatalLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Crear Nueva Aula en {{ faculty.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nombre*</label>
                                <input id="name" type="text" v-model="form.name" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label for="capacity" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Capacidad*</label>
                                <input id="capacity" type="number" v-model="form.capacity" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                <div v-if="form.errors.capacity" class="text-red-500 text-sm mt-1">{{ form.errors.capacity }}</div>
                            </div>

                            <div>
                                <label for="services" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Servicios*</label>
                                <textarea id="services" v-model="form.services" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600"></textarea>
                                <div v-if="form.errors.services" class="text-red-500 text-sm mt-1">{{ form.errors.services }}</div>
                            </div>

                            <div>
                                <label for="responsible" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Responsable</label>
                                <select id="responsible" v-model="form.responsible"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                                    <option value="">Seleccione un responsable</option>
                                    <option v-for="user in classroomUsers" :key="user.id" :value="user.id">
                                        {{ user.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.responsible" class="text-red-500 text-sm mt-1">{{ form.errors.responsible }}</div>
                                <div v-if="classroomUsers.length === 0" class="text-yellow-500 text-sm mt-1">
                                    No hay usuarios con rol de responsable disponibles.
                                </div>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Correo</label>
                                <input id="email" type="email" v-model="form.email"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Teléfono</label>
                                <input id="phone" type="text" v-model="form.phone"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                <div v-if="form.errors.phone" class="text-red-500 text-sm mt-1">{{ form.errors.phone }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Imagen</label>
                                <div class="flex space-x-4 mb-2">
                                    <label class="flex items-center">
                                        <input type="radio" v-model="form.image_option" value="url" class="mr-2" />
                                        URL
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" v-model="form.image_option" value="upload" class="mr-2" />
                                        Subir archivo
                                    </label>
                                </div>
                                <div v-if="form.image_option === 'url'">
                                    <input id="image_url" type="url" v-model="form.image_url"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                    <div v-if="form.errors.image_url" class="text-red-500 text-sm mt-1">{{ form.errors.image_url }}</div>
                                </div>
                                <div v-else>
                                    <input id="image_file" type="file" accept="image/*" @change="handleImageFile"
                                        class="mt-1 block w-full" />
                                    <div v-if="form.errors.image_file" class="text-red-500 text-sm mt-1">{{ form.errors.image_file }}</div>
                                </div>
                            </div>

                            <div>
  
                                <div v-if="form.errors.uses_db_storage" class="text-red-500 text-sm mt-1">{{ form.errors.uses_db_storage }}</div>
                            </div>

                            <div class="flex justify-end space-x-4 pt-6">
                                <Link :href="route('admin.estatal.faculties.index')"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
                                    Cancelar
                                </Link>
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    :disabled="form.processing">
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ form.processing ? 'Creando...' : 'Crear Aula' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminEstatalLayout>
</template>