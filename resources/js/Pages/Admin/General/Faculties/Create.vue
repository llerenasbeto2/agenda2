<script setup>
import AdminGeneralLayout from '@/Layouts/AdminGeneralLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    municipalities: {
        type: Array,
        required: true,
        default: () => []
    },
    users: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    name: '',
    location: '',
    responsible: '',
    email: '',
    phone: '',
    municipality_id: props.municipalities[0]?.id || '',
    services: '',
    description: '',
    web_site: '',
    image_option: 'url',
    image_url: '',
    image_file: null,
    classrooms: [],
    // capacity eliminado del formulario
});

const classrooms = ref([]);

const adminUsers = computed(() => props.users?.filter(u => u.rol_id === 3) || []);
const classroomUsers = computed(() => props.users?.filter(u => u.rol_id === 2) || []);

const addClassroom = () => {
    classrooms.value.push({
        name: '',
        capacity: '',
        services: '',
        responsible: '',
        email: '',
        phone: '',
        web_site: '',
        image_option: 'url',
        image_url: '',
        image_file: null,
        uses_db_storage: false,
    });
};

const removeClassroom = (index) => {
    classrooms.value.splice(index, 1);
};

const handleImageFile = (event, target) => {
    if (event.target.files && event.target.files[0]) {
        target.image_file = event.target.files[0];
    }
};

const submit = () => {
    form.classrooms = classrooms.value.map(classroom => ({
        ...classroom,
        image_file: classroom.image_option === 'upload' ? classroom.image_file : null,
    }));
    
    form.post(route('admin.general.faculties.store'), {
        onSuccess: () => {
            form.reset();
            classrooms.value = [];
        },
    });
};
</script>

<template>
    <Head title="Crear Facultad" />

    <AdminGeneralLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Crear Nueva Facultad
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nombre*</label>
                                    <input id="name" type="text" v-model="form.name" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                    <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
                                </div>

                                <div>
                                    <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Ubicación*</label>
                                    <input id="location" type="text" v-model="form.location" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                    <div v-if="form.errors.location" class="text-red-500 text-sm mt-1">{{ form.errors.location }}</div>
                                </div>

                                <div>
                                    <label for="responsible" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Responsable*</label>
                                    <select id="responsible" v-model="form.responsible" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                                        <option value="">Seleccione un responsable</option>
                                        <option v-for="user in adminUsers" :key="user.id" :value="user.id">
                                            {{ user.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.responsible" class="text-red-500 text-sm mt-1">{{ form.errors.responsible }}</div>
                                    <div v-if="adminUsers.length === 0" class="text-yellow-500 text-sm mt-1">
                                        No hay usuarios con rol de administrador disponibles.
                                    </div>
                                </div>

                                <div>
                                    <label for="municipality_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Municipio*</label>
                                    <select id="municipality_id" v-model="form.municipality_id" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                                        <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">
                                            {{ municipality.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.municipality_id" class="text-red-500 text-sm mt-1">{{ form.errors.municipality_id }}</div>
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
                                    <label for="web_site" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Sitio Web</label>
                                    <input id="web_site" type="url" v-model="form.web_site"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                    <div v-if="form.errors.web_site" class="text-red-500 text-sm mt-1">{{ form.errors.web_site }}</div>
                                </div>
                            </div>

                            <div>
                                <label for="services" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Servicios*</label>
                                <textarea id="services" v-model="form.services" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600"></textarea>
                                <div v-if="form.errors.services" class="text-red-500 text-sm mt-1">{{ form.errors.services }}</div>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Descripción*</label>
                                <textarea id="description" v-model="form.description" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600"></textarea>
                                <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</div>
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
                                    <input id="image_file" type="file" accept="image/*" @change="handleImageFile($event, form)"
                                        class="mt-1 block w-full" />
                                    <div v-if="form.errors.image_file" class="text-red-500 text-sm mt-1">{{ form.errors.image_file }}</div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-medium text-gray-700 dark:text-gray-200 mb-4">Aulas</h3>
                                <button type="button" @click="addClassroom"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    Agregar Aula
                                </button>

                                <div v-for="(classroom, index) in classrooms" :key="index" class="border p-4 rounded-lg mb-4 mt-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label :for="`classroom_name_${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nombre del Aula*</label>
                                            <input :id="`classroom_name_${index}`" type="text" v-model="classroom.name" required
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                            <div v-if="form.errors[`classrooms.${index}.name`]" class="text-red-500 text-sm mt-1">
                                                {{ form.errors[`classrooms.${index}.name`] }}
                                            </div>
                                        </div>

                                        <div>
                                            <label :for="`classroom_capacity_${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Capacidad*</label>
                                            <input :id="`classroom_capacity_${index}`" type="number" v-model="classroom.capacity" required
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                            <div v-if="form.errors[`classrooms.${index}.capacity`]" class="text-red-500 text-sm mt-1">
                                                {{ form.errors[`classrooms.${index}.capacity`] }}
                                            </div>
                                        </div>

                                        <div>
                                            <label :for="`classroom_responsible_${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Responsable</label>
                                            <select :id="`classroom_responsible_${index}`" v-model="classroom.responsible"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                                                <option value="">Seleccione un responsable</option>
                                                <option v-for="user in classroomUsers" :key="user.id" :value="user.id">
                                                    {{ user.name }}
                                                </option>
                                            </select>
                                            <div v-if="form.errors[`classrooms.${index}.responsible`]" class="text-red-500 text-sm mt-1">
                                                {{ form.errors[`classrooms.${index}.responsible`] }}
                                            </div>
                                        </div>

                                        <div>
                                            <label :for="`classroom_email_${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Correo</label>
                                            <input :id="`classroom_email_${index}`" type="email" v-model="classroom.email"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                            <div v-if="form.errors[`classrooms.${index}.email`]" class="text-red-500 text-sm mt-1">
                                                {{ form.errors[`classrooms.${index}.email`] }}
                                            </div>
                                        </div>

                                        <div>
                                            <label :for="`classroom_phone_${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Teléfono</label>
                                            <input :id="`classroom_phone_${index}`" type="text" v-model="classroom.phone"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                            <div v-if="form.errors[`classrooms.${index}.phone`]" class="text-red-500 text-sm mt-1">
                                                {{ form.errors[`classrooms.${index}.phone`] }}
                                            </div>
                                        </div>

                                        <div>
                                            <label :for="`classroom_web_site_${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Sitio Web</label>
                                            <input :id="`classroom_web_site_${index}`" type="url" v-model="classroom.web_site"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                            <div v-if="form.errors[`classrooms.${index}.web_site`]" class="text-red-500 text-sm mt-1">
                                                {{ form.errors[`classrooms.${index}.web_site`] }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <label :for="`classroom_services_${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Servicios*</label>
                                        <textarea :id="`classroom_services_${index}`" v-model="classroom.services" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600"></textarea>
                                        <div v-if="form.errors[`classrooms.${index}.services`]" class="text-red-500 text-sm mt-1">
                                            {{ form.errors[`classrooms.${index}.services`] }}
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Imagen del Aula</label>
                                        <div class="flex space-x-4 mb-2">
                                            <label class="flex items-center">
                                                <input type="radio" v-model="classroom.image_option" value="url" class="mr-2" />
                                                URL
                                            </label>
                                            <label class="flex items-center">
                                                <input type="radio" v-model="classroom.image_option" value="upload" class="mr-2" />
                                                Subir archivo
                                            </label>
                                        </div>
                                        <div v-if="classroom.image_option === 'url'">
                                            <input :id="`classroom_image_url_${index}`" type="url" v-model="classroom.image_url"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                            <div v-if="form.errors[`classrooms.${index}.image_url`]" class="text-red-500 text-sm mt-1">
                                                {{ form.errors[`classrooms.${index}.image_url`] }}
                                            </div>
                                        </div>
                                        <div v-else>
                                            <input :id="`classroom_image_file_${index}`" type="file" accept="image/*" @change="handleImageFile($event, classroom)"
                                                class="mt-1 block w-full" />
                                            <div v-if="form.errors[`classrooms.${index}.image_file`]" class="text-red-500 text-sm mt-1">
                                                {{ form.errors[`classrooms.${index}.image_file`] }}
                                            </div>
                                        </div>
                                    </div>

                            

                                    <button type="button" @click="removeClassroom(index)"
                                        class="mt-4 inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        Eliminar Aula
                                    </button>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-4 pt-6">
                                <Link :href="route('admin.general.faculties.index')"
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
                                    {{ form.processing ? 'Creando...' : 'Crear Facultad' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminGeneralLayout>
</template>