<script setup>
import AdminEstatalLayout from '@/Layouts/AdminEstatalLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    classroom: Object,
    faculty: Object,
    municipalities: Array,
    users: Array,
});

const form = useForm({
    name: props.classroom.name || '',
    capacity: props.classroom.capacity || '',
    services: props.classroom.services || '',
    responsible: props.classroom.responsible || '',
    email: props.classroom.email || '',
    phone: props.classroom.phone || '',
    image_option: props.classroom.image_url ? 'url' : props.classroom.image_path ? 'upload' : 'url',
    image_url: props.classroom.image_url || '',
    image_file: null,
    uses_db_storage: props.classroom.uses_db_storage || false,
});

const classroomUsers = ref(props.users.filter(u => u.rol_id === 2));

const handleImageFile = (event) => {
    if (event.target.files && event.target.files[0]) {
        form.image_file = event.target.files[0];
    }
};

const submit = () => {
    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('name', form.name);
    formData.append('capacity', form.capacity);
    formData.append('services', form.services);
    formData.append('responsible', form.responsible);
    formData.append('email', form.email);
    formData.append('phone', form.phone);
    formData.append('image_option', form.image_option);
    formData.append('uses_db_storage', form.uses_db_storage ? '1' : '0');
    
    if (form.image_option === 'upload' && form.image_file) {
        formData.append('image_file', form.image_file);
    } else if (form.image_option === 'url') {
        formData.append('image_url', form.image_url);
    }

    router.post(route('admin.estatal.faculties.update', props.classroom.id), formData, {
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
    <Head title="Editar Aula" />

    <AdminEstatalLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Editar Aula en {{ faculty.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded">
                    {{ $page.props.flash.success }}
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
                                <label for="capacity" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Capacidad</label>
                                <input id="capacity" type="number" v-model="form.capacity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
                                <div v-if="form.errors.capacity" class="text-red-500 text-sm mt-1">{{ form.errors.capacity }}</div>
                            </div>

                            <div>
                                <label for="services" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Servicios</label>
                                <textarea id="services" v-model="form.services" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600"></textarea>
                                <div v-if="form.errors.services" class="text-red-500 text-sm mt-1">{{ form.errors.services }}</div>
                            </div>

                            <div>
                                <label for="responsible" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Responsable</label>
                                <select id="responsible" v-model="form.responsible" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600">
                                    <option value="">Seleccione un responsable</option>
                                    <option v-for="user in classroomUsers" :key="user.id" :value="user.id">{{ user.name }}</option>
                                </select>
                                <div v-if="form.errors.responsible" class="text-red-500 text-sm mt-1">{{ form.errors.responsible }}</div>
                                <div v-if="classroomUsers.length === 0" class="text-yellow-500 text-sm mt-1">
                                    No hay usuarios con rol de responsable disponibles.
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
                                    <input id="image_file" type="file" accept="image/*" @change="handleImageFile" class="mt-1 block w-full" />
                                    <div v-if="form.errors.image_file" class="text-red-500 text-sm mt-1">{{ form.errors.image_file }}</div>
                                    <div v-if="(props.classroom.image_url || props.classroom.image_path) && !form.image_file" class="mt-2">
                                        <img :src="props.classroom.image_url || `/storage/${props.classroom.image_path}`" alt="Current Image" class="w-32 h-32 object-cover rounded" />
                                    </div>
                                </div>
                            </div>

  

                            <div class="flex justify-end space-x-4">
                                <Link :href="route('admin.estatal.faculties.index')" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Cancelar
                                </Link>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" :disabled="form.processing">
                                    {{ form.processing ? 'Actualizando...' : 'Actualizar Aula' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminEstatalLayout>
</template>