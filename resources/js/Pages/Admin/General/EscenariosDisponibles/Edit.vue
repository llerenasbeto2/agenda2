<script setup>
import AdminGeneralLayout from '@/Layouts/AdminGeneralLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    space: {
        type: Object,
        required: true
    }
});

const form = useForm({
    name: props.space.name,
    location: props.space.location,
    capacity: props.space.capacity,
    services: props.space.services,
    responsible: props.space.responsible,
    email: props.space.email,
    phone: props.space.phone,
    image: props.space.image,
    description: props.space.description || '',
    municipality_id: props.space.municipality_id || null
});

const submit = () => {
    console.log('Datos enviados:', form);
    form.put(route('admin.general.escenariosDisponibles.update', props.space.id), {
        onSuccess: () => {
            console.log('Actualización exitosa');
        },
        onError: (errors) => {
            console.log('Errores:', errors);
        }
    });
};
</script>

<template>
    <Head title="Editar Escenario" />

    <AdminGeneralLayout>
        <template #header>
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white text-center">
                Editar Escenario
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg overflow-hidden">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                                <input 
                                    type="text" 
                                    v-model="form.name" 
                                    id="name"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación</label>
                                <textarea 
                                    v-model="form.location" 
                                    id="location"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                                <div v-if="form.errors.location" class="mt-1 text-sm text-red-600">{{ form.errors.location }}</div>
                            </div>

                            <div>
                                <label for="capacity" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Capacidad</label>
                                <input 
                                    type="number" 
                                    v-model="form.capacity" 
                                    id="capacity"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                <div v-if="form.errors.capacity" class="mt-1 text-sm text-red-600">{{ form.errors.capacity }}</div>
                            </div>

                            <div>
                                <label for="services" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Servicios</label>
                                <textarea 
                                    v-model="form.services" 
                                    id="services"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                                <div v-if="form.errors.services" class="mt-1 text-sm text-red-600">{{ form.errors.services }}</div>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                                <textarea 
                                    v-model="form.description" 
                                    id="description"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                                <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</div>
                            </div>

                            <div>
                                <label for="responsible" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Responsable</label>
                                <input 
                                    type="text" 
                                    v-model="form.responsible" 
                                    id="responsible"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                <div v-if="form.errors.responsible" class="mt-1 text-sm text-red-600">{{ form.errors.responsible }}</div>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                <input 
                                    type="email" 
                                    v-model="form.email" 
                                    id="email"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</div>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
                                <input 
                                    type="text" 
                                    v-model="form.phone" 
                                    id="phone"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                <div v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</div>
                            </div>

                            <div>
                                <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Imagen (URL)</label>
                                <input 
                                    type="text" 
                                    v-model="form.image" 
                                    id="image"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                <div v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</div>
                            </div>

                            <!-- Opcional: Campo para municipality_id -->
                            <div>
                                <label for="municipality_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Municipio (Opcional)</label>
                                <input 
                                    type="number" 
                                    v-model="form.municipality_id" 
                                    id="municipality_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                <div v-if="form.errors.municipality_id" class="mt-1 text-sm text-red-600">{{ form.errors.municipality_id }}</div>
                            </div>

                            <div class="flex justify-end space-x-4">
                                <Link 
                                    :href="route('admin.general.escenariosDisponibles.index')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-500 dark:bg-gray-600 hover:bg-gray-600 dark:hover:bg-gray-700 text-white font-semibold rounded-md transition-colors duration-200"
                                >
                                    Cancelar
                                </Link>
                                <button 
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-500 hover:bg-indigo-700 dark:hover:bg-indigo-600 text-white font-semibold rounded-md transition-colors duration-200"
                                >
                                    Actualizar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminGeneralLayout>
</template>