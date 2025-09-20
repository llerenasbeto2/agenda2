<script setup>
import AdminGeneralLayout from '@/Layouts/AdminGeneralLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    faculty: Object,
    municipalities: Array,
    users: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.faculty.name || '',
    location: props.faculty.location || '',
    responsible: props.faculty.responsible || '',
    email: props.faculty.email || '',
    phone: props.faculty.phone || '',
    municipality_id: props.faculty.municipality_id || 1,
    services: props.faculty.services || '',
    description: props.faculty.description || '',
    web_site: props.faculty.web_site || '',
    image_option: props.faculty.image ? (props.faculty.image.includes('http') ? 'url' : 'upload') : 'url',
    image_url: props.faculty.image && props.faculty.image.includes('http') ? props.faculty.image : '',
    image_file: null,
    // capacity eliminado del formulario
});

const classrooms = ref(props.faculty.classrooms.map(classroom => ({
    id: classroom.id,
    name: classroom.name || '',
    capacity: classroom.capacity || '',
    services: classroom.services || '',
    responsible: classroom.responsible || '',
    email: classroom.email || '',
    phone: classroom.phone || '',
    image_option: classroom.image_url ? 'url' : classroom.image_path ? 'upload' : 'url',
    image_url: classroom.image_url || '',
    image_file: null,
    uses_db_storage: classroom.uses_db_storage || false,
})));

const addClassroom = () => {
    classrooms.value.push({
        id: null,
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
    const formData = new FormData();
    
    // Add faculty data
    formData.append('_method', 'PUT');
    formData.append('name', form.name);
    formData.append('location', form.location);
    formData.append('responsible', form.responsible);
    formData.append('email', form.email);
    formData.append('phone', form.phone);
    formData.append('municipality_id', form.municipality_id);
    formData.append('services', form.services);
    formData.append('description', form.description);
    formData.append('web_site', form.web_site);
    formData.append('image_option', form.image_option);
    
    // Handle faculty image
    if (form.image_option === 'upload' && form.image_file) {
        formData.append('image_file', form.image_file);
    } else if (form.image_option === 'url') {
        formData.append('image_url', form.image_url);
    }
    
    // Add classrooms data
    classrooms.value.forEach((classroom, index) => {
        formData.append(`classrooms[${index}][id]`, classroom.id || '');
        formData.append(`classrooms[${index}][name]`, classroom.name);
        formData.append(`classrooms[${index}][capacity]`, classroom.capacity);
        formData.append(`classrooms[${index}][services]`, classroom.services);
        formData.append(`classrooms[${index}][responsible]`, classroom.responsible);
        formData.append(`classrooms[${index}][email]`, classroom.email);
        formData.append(`classrooms[${index}][phone]`, classroom.phone);
        formData.append(`classrooms[${index}][uses_db_storage]`, classroom.uses_db_storage ? '1' : '0');
        formData.append(`classrooms[${index}][image_option]`, classroom.image_option);
        
        if (classroom.image_option === 'upload' && classroom.image_file) {
            formData.append(`classrooms[${index}][image_file]`, classroom.image_file);
        } else if (classroom.image_option === 'url') {
            formData.append(`classrooms[${index}][image_url]`, classroom.image_url);
        }
    });
    
    router.post(route('admin.general.faculties.update', props.faculty.id), formData, {
        preserveState: true,
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            console.log('Update successful');
        },
        onError: (errors) => {
            console.error('Update errors:', errors);
        },
    });
};
</script>

<template>
    <Head title="Editar Facultad" />

    <AdminGeneralLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Editar Facultad
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash.info" class="mb-4 p-4 bg-blue-100 text-blue-700 rounded">
                    {{ $page.props.flash.info }}
                </div>
                <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                    {{ $page.props.flash.error }}
                </div>
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <form @submit.prevent="submit" enctype="multipart/form-data" class="space-y-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nombre</label>
                                <input id="name" type="text" v-model="form.name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Ubicación</label>
                                <input id="location" type="text" v-model="form.location" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                <div v-if="form.errors.location" class="text-red-500 text-sm mt-1">{{ form.errors.location }}</div>
                            </div>

                            <div>
                                <label for="responsible" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Responsable</label>
                                <select id="responsible" v-model="form.responsible" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                                    <option value="">Seleccione un responsable</option>
                                    <option v-for="user in Array.isArray(props.users) ? props.users.filter(u => u.rol_id === 3) : []" :key="user.id" :value="user.id">{{ user.name }}</option>
                                </select>
                                <div v-if="form.errors.responsible" class="text-red-500 text-sm mt-1">{{ form.errors.responsible }}</div>
                                <div v-if="Array.isArray(props.users) && props.users.filter(u => u.rol_id === 3).length === 0" class="text-yellow-500 text-sm mt-1">
                                    No hay usuarios con rol_id 3 disponibles.
                                </div>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Correo</label>
                                <input id="email" type="email" v-model="form.email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Teléfono</label>
                                <input id="phone" type="text" v-model="form.phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                <div v-if="form.errors.phone" class="text-red-500 text-sm mt-1">{{ form.errors.phone }}</div>
                            </div>

                            <div>
                                <label for="municipality_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Municipio</label>
                                <select id="municipality_id" v-model="form.municipality_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                                    <option v-for="municipality in municipalities" :key="municipality.id" :value="municipality.id">{{ municipality.name }}</option>
                                </select>
                                <div v-if="form.errors.municipality_id" class="text-red-500 text-sm mt-1">{{ form.errors.municipality_id }}</div>
                            </div>

                            <div>
                                <label for="services" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Servicios</label>
                                <textarea id="services" v-model="form.services" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600"></textarea>
                                <div v-if="form.errors.services" class="text-red-500 text-sm mt-1">{{ form.errors.services }}</div>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Descripción</label>
                                <textarea id="description" v-model="form.description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600"></textarea>
                                <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</div>
                            </div>

                            <div>
                                <label for="web_site" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Sitio Web</label>
                                <input id="web_site" type="url" v-model="form.web_site" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                <div v-if="form.errors.web_site" class="text-red-500 text-sm mt-1">{{ form.errors.web_site }}</div>
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
                                    <input id="image_url" type="url" v-model="form.image_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                    <div v-if="form.errors.image_url" class="text-red-500 text-sm mt-1">{{ form.errors.image_url }}</div>
                                </div>
                                <div v-else>
                                    <input id="image_file" type="file" accept="image/*" @change="handleImageFile($event, form)" class="mt-1 block w-full" />
                                    <div v-if="form.errors.image_file" class="text-red-500 text-sm mt-1">{{ form.errors.image_file }}</div>
                                    <div v-if="props.faculty.image && !form.image_file" class="mt-2">
                                        <img :src="props.faculty.image.includes('http') ? props.faculty.image : `/storage/${props.faculty.image}`" alt="Current Image" class="w-32 h-32 object-cover rounded" />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-lg font-medium text-gray-700 dark:text-gray-200">Aulas</h3>
                                <button type="button" @click="addClassroom" class="inline-block mb-4 px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700">
                                    Agregar Aula
                                </button>
                                <div v-for="(classroom, index) in classrooms" :key="index" class="border p-4 rounded-lg mb-4">
                                    <div>
                                        <label :for="'classroom_name_' + index" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nombre del Aula</label>
                                        <input :id="'classroom_name_' + index" type="text" v-model="classroom.name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                        <div v-if="form.errors[`classrooms.${index}.name`]" class="text-red-500 text-sm mt-1">{{ form.errors[`classrooms.${index}.name`] }}</div>
                                    </div>
                                    <div>
                                        <label :for="'classroom_capacity_' + index" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Capacidad</label>
                                        <input :id="'classroom_capacity_' + index" type="number" v-model="classroom.capacity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                        <div v-if="form.errors[`classrooms.${index}.capacity`]" class="text-red-500 text-sm mt-1">{{ form.errors[`classrooms.${index}.capacity`] }}</div>
                                    </div>
                                    <div>
                                        <label :for="'classroom_services_' + index" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Servicios</label>
                                        <textarea :id="'classroom_services_' + index" v-model="classroom.services" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600"></textarea>
                                        <div v-if="form.errors[`classrooms.${index}.services`]" class="text-red-500 text-sm mt-1">{{ form.errors[`classrooms.${index}.services`] }}</div>
                                    </div>
                                    <div>
                                        <label :for="'classroom_responsible_' + index" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Responsable</label>
                                        <select :id="'classroom_responsible_' + index" v-model="classroom.responsible" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                                            <option value="">Seleccione un responsable</option>
                                            <option v-for="user in Array.isArray(props.users) ? props.users.filter(u => u.rol_id === 2) : []" :key="user.id" :value="user.id">{{ user.name }}</option>
                                        </select>
                                        <div v-if="form.errors[`classrooms.${index}.responsible`]" class="text-red-500 text-sm mt-1">{{ form.errors[`classrooms.${index}.responsible`] }}</div>
                                        <div v-if="Array.isArray(props.users) && props.users.filter(u => u.rol_id === 2).length === 0" class="text-yellow-500 text-sm mt-1">
                                            No hay usuarios con rol_id 2 disponibles.
                                        </div>
                                    </div>
                                    <div>
                                        <label :for="'classroom_email_' + index" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Correo</label>
                                        <input :id="'classroom_email_' + index" type="email" v-model="classroom.email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                        <div v-if="form.errors[`classrooms.${index}.email`]" class="text-red-500 text-sm mt-1">{{ form.errors[`classrooms.${index}.email`] }}</div>
                                    </div>
                                    <div>
                                        <label :for="'classroom_phone_' + index" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Teléfono</label>
                                        <input :id="'classroom_phone_' + index" type="text" v-model="classroom.phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                        <div v-if="form.errors[`classrooms.${index}.phone`]" class="text-red-500 text-sm mt-1">{{ form.errors[`classrooms.${index}.phone`] }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Imagen</label>
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
                                            <input :id="'classroom_image_url_' + index" type="url" v-model="classroom.image_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                            <div v-if="form.errors[`classrooms.${index}.image_url`]" class="text-red-500 text-sm mt-1">{{ form.errors[`classrooms.${index}.image_url`] }}</div>
                                        </div>
                                        <div v-else>
                                            <input :id="'classroom_image_file_' + index" type="file" accept="image/*" @change="handleImageFile($event, classroom)" class="mt-1 block w-full" />
                                            <div v-if="form.errors[`classrooms.${index}.image_file`]" class="text-red-500 text-sm mt-1">{{ form.errors[`classrooms.${index}.image_file`] }}</div>
                                            <div v-if="(classroom.image_url || classroom.image_path) && !classroom.image_file" class="mt-2">
                                                <img :src="classroom.image_url || `/storage/${classroom.image_path}`" alt="Current Image" class="w-32 h-32 object-cover rounded" />
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                       
                                        <input :id="'classroom_uses_db_storage_' + index" type="checkbox" v-model="classroom.uses_db_storage" class="mt-1 rounded border-gray-300 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                        <div v-if="form.errors[`classrooms.${index}.uses_db_storage`]" class="text-red-500 text-sm mt-1">{{ form.errors[`classrooms.${index}.uses_db_storage`] }}</div>
                                    </div>
                                    <button type="button" @click="removeClassroom(index)" class="inline-block mt-2 px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700">
                                        Eliminar Aula
                                    </button>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-4">
                                <button :href="route('admin.general.faculties.index')" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Cancelar
                                </button>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" :disabled="form.processing">
                                    {{ form.processing ? 'Actualizando...' : 'Actualizar Facultad' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminGeneralLayout>
</template>