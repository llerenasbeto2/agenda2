<script setup>
import AdminEstatalLayout from '@/Layouts/AdminEstatalLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    category: Object,
    
});

const form = useForm({
    name: props.category.name,
    id: props.category.id,
    
});

function submit() {
    form.put(route('admin.estatal.categories.update', props.category.id), {
        onSuccess: () => {
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Editar Categoría" />

    <AdminEstatalLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
               Editar Categoría
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Nombre de la Categoría
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

                            

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    :disabled="form.processing"
                                >
                                    {{ form.processing ? 'Actualizando...' : 'Actualizar Categoría' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminEstatalLayout>
</template>