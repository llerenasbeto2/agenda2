<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import DynamicLayout from '@/Layouts/DynamicLayout.vue';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth.user);
const showSuccessModal = ref(false);

const form = useForm({
    full_name: '',
    anonymous: false,
    category: '',
    message: '',
});

// Pre-fill fields for authenticated users
if (isAuthenticated.value) {
    form.full_name = page.props.auth.user.name || '';
}

const categories = ref(['Queja', 'Sugerencia', 'Comentario']);

// Watch for success flash message to show modal
watch(() => page.props.flash.success, (newSuccess) => {
    if (newSuccess) {
        showSuccessModal.value = true;
    }
});

const submitComplaint = () => {
    form.post(route('quejas.store'), {
        onSuccess: () => {
            form.reset();
        },
        onError: () => {
            console.error('Errores:', form.errors);
        },
        preserveState: true, // Preserve flash messages and errors
    });
};

const closeModal = () => {
    showSuccessModal.value = false;
};
</script>

<template>
    <Head title="Quejas y Sugerencias" />

    <DynamicLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Quejas y Sugerencias
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-lg sm:rounded-lg dark:bg-gray-800 border border-teal-100 dark:border-teal-900 p-6">
                    <h2 class="text-4xl font-semibold text-teal-700 dark:text-teal-300 text-center mb-6">
                        Quejas y Sugerencias
                    </h2>

                    <form @submit.prevent="submitComplaint" class="space-y-6">
                        <div class="form-group">
                            <label class="block text-gray-700 dark:text-gray-200">Nombre Completo (opcional):</label>
                            <input
                                v-model="form.full_name"
                                :disabled="form.anonymous"
                                type="text"
                                class="form-input"
                                placeholder="Escribe tu nombre completo"
                            />
                            <span v-if="form.errors.full_name" class="error-message">{{ form.errors.full_name }}</span>
                        </div>

                        <!-- Campo de email para usuarios autenticados -->
                        <div class="form-group" v-if="isAuthenticated">
                            <label class="block text-gray-700 dark:text-gray-200">Correo Electrónico:</label>
                            <input
                                :value="page.props.auth.user.email"
                                type="email"
                                class="form-input bg-gray-100 dark:bg-gray-600"
                                readonly
                                disabled
                            />
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Este correo se tomará automáticamente de tu cuenta
                            </p>
                        </div>

                        <!-- Mensaje informativo para usuarios no autenticados -->
                        <div class="form-group" v-if="!isAuthenticated">
                            <div class="bg-blue-100 dark:bg-blue-900 border border-blue-300 dark:border-blue-700 rounded-lg p-4">
                                <p class="text-blue-800 dark:text-blue-200">
                                    <strong>Información:</strong> Tu mensaje será enviado usando nuestro sistema de contacto. 
                                    Si deseas que podamos contactarte directamente, te recomendamos 
                                    <a href="/login" class="underline hover:text-blue-600">iniciar sesión</a> 
                                    o <a href="/register" class="underline hover:text-blue-600">registrarte</a>.
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="block text-gray-700 dark:text-gray-200">Categoría:</label>
                            <select v-model="form.category" class="form-select">
                                <option disabled value="">Selecciona una categoría</option>
                                <option v-for="category in categories" :key="category" :value="category">
                                    {{ category }}
                                </option>
                            </select>
                            <span v-if="form.errors.category" class="error-message">{{ form.errors.category }}</span>
                        </div>

                        <div class="form-group">
                            <label class="block text-gray-700 dark:text-gray-200">Mensaje:</label>
                            <textarea
                                v-model="form.message"
                                class="form-textarea"
                                rows="4"
                                placeholder="Escribe tu mensaje aquí"
                            ></textarea>
                            <span v-if="form.errors.message" class="error-message">{{ form.errors.message }}</span>
                        </div>

                        <div v-if="form.errors.error" class="error-message text-center">
                            {{ form.errors.error }}
                        </div>

                        <button 
                            type="submit" 
                            class="submit-button" 
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Enviando...' : 'Enviar' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Success Modal -->
        <div v-if="showSuccessModal" class="modal-overlay">
            <div class="modal-content">
                <h3 class="modal-title">¡Enviado Correctamente!</h3>
                <p class="modal-message">{{ page.props.flash.success }}</p>
                <button class="modal-button" @click="closeModal">Cerrar</button>
            </div>
        </div>
    </DynamicLayout>
</template>

<style scoped>
/* Existing form styles */
.form-group {
    margin-bottom: 1rem;
    transition: transform 0.3s ease;
}

.form-group:hover {
    transform: translateY(-2px);
}

.form-input,
.form-select,
.form-textarea,
.form-checkbox {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    background-color: white;
}

.form-checkbox {
    width: auto;
    padding: 0;
}

.dark .form-input,
.dark .form-select,
.dark .form-textarea,
.dark .form-checkbox {
    background-color: #374151;
    border-color: #4b5563;
    color: #e5e7eb;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus,
.form-checkbox:focus {
    outline: none;
    border-color: #004d40;
    box-shadow: 0 0 0 3px rgba(0, 77, 64, 0.2);
    transform: translateY(-1px);
}

.dark .form-input:focus,
.dark .form-select:focus,
.dark .form-textarea:focus,
.dark .form-checkbox:focus {
    border-color: #2dd4bf;
    box-shadow: 0 0 0 3px rgba(45, 212, 191, 0.2);
}

.submit-button {
    width: 100%;
    padding: 12px;
    background-color: #004d40;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.submit-button:hover {
    background-color: #00695c;
    transform: translateY(-2px);
    box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
}

.submit-button:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.submit-button:disabled {
    background-color: #cccccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.error-message {
    color: #e53e3e;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    animation: fadeIn 0.3s ease-in-out;
}

/* Modal styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    padding: 24px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    max-width: 400px;
    width: 90%;
    animation: slideIn 0.3s ease-out;
}

.dark .modal-content {
    background: #1f2937;
    color: #e5e7eb;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #004d40;
    margin-bottom: 16px;
}

.dark .modal-title {
    color: #2dd4bf;
}

.modal-message {
    font-size: 1rem;
    color: #374151;
    margin-bottom: 24px;
}

.dark .modal-message {
    color: #d1d5db;
}

.modal-button {
    padding: 10px 20px;
    background-color: #004d40;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.modal-button:hover {
    background-color: #00695c;
    transform: translateY(-2px);
}

.modal-button:active {
    transform: translateY(0);
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>