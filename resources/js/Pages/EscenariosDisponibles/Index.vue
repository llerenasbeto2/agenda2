<script setup>
import DynamicLayout from '@/Layouts/DynamicLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Leyenda from '@/Components/Leyenda.vue';

const props = defineProps({
    faculties: {
        type: Array,
        required: true,
        default: () => [],
    },
    municipalities: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const searchQuery = ref('');
const selectedMunicipality = ref('');
const showClassroomsList = ref(false);
const showClassroomDetail = ref(false);
const selectedFaculty = ref(null);
const selectedClassroom = ref(null);

const imageUrlForm = useForm({
    image_url: '',
});

const allClassrooms = computed(() => {
    return props.faculties.flatMap(faculty =>
        faculty.classrooms.map(classroom => ({
            ...classroom,
            faculty_name: faculty.name,
            faculty_location: faculty.location,
            faculty_image: faculty.image,
            faculty_id: faculty.id,
            web_site: faculty.web_site,
            email: classroom.email || faculty.email,
            phone: classroom.phone || faculty.phone,
            image: classroom.image_url || classroom.image_path,
            number: classroom.capacity,
            responsible: classroom.responsible || faculty.responsible,
            services: classroom.services || faculty.services,
            municipality_id: faculty.municipality_id,
        }))
    );
});

const filteredClassrooms = computed(() => {
    return allClassrooms.value.filter(classroom => {
        const matchesSearch = !searchQuery.value || 
            (classroom.faculty_name && classroom.faculty_name.toLowerCase().includes(searchQuery.value.toLowerCase()));
        
        const matchesMunicipality = !selectedMunicipality.value || 
            classroom.municipality_id === parseInt(selectedMunicipality.value);
        
        return matchesSearch && matchesMunicipality;
    });
});

const filteredFaculties = computed(() => {
    const grouped = filteredClassrooms.value.reduce((acc, classroom) => {
        if (!acc[classroom.faculty_id]) {
            const faculty = props.faculties.find(f => f.id === classroom.faculty_id);
            if (faculty) {
                acc[classroom.faculty_id] = {
                    ...faculty,
                    classrooms: [],
                };
            }
        }
        acc[classroom.faculty_id].classrooms.push(classroom);
        return acc;
    }, {});
    return Object.values(grouped);
});

const showClassrooms = (faculty) => {
    selectedFaculty.value = faculty;
    showClassroomsList.value = true;
    showClassroomDetail.value = false;
};

const showClassroomDetails = (classroom) => {
    selectedClassroom.value = classroom;
    showClassroomDetail.value = true;
};

const closeModals = () => {
    showClassroomsList.value = false;
    showClassroomDetail.value = false;
    selectedFaculty.value = null;
    selectedClassroom.value = null;
};

const goBackToList = () => {
    showClassroomDetail.value = false;
    selectedClassroom.value = null;
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedMunicipality.value = '';
};

const updateImageUrl = () => {
    if (!imageUrlForm.image_url || !selectedClassroom.value) return;
    imageUrlForm.post(route('classrooms.image-url.update', {
        classroom: selectedClassroom.value.id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            imageUrlForm.reset();
            selectedClassroom.value.image = imageUrlForm.image_url + (imageUrlForm.image_url.includes('?') ? '&' : '?') + 't=' + Date.now();
        },
        onError: () => {
            alert('Error al actualizar la URL de la imagen');
        },
    });
};

const page = usePage();
</script>

<template>
    <Head title="Escenarios Disponibles" />

    <DynamicLayout>
        <template #header>
            <h2 class="text-4xl font-semibold leading-tight text-teal-700 dark:text-teal-300 text-center">
                Escenarios Disponibles
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-lg sm:rounded-lg dark:bg-gray-800 border border-teal-100 dark:border-teal-900">
                    <div class="p-6 text-gray-600 dark:text-gray-100">
                        <div class="main-container">
                            <!-- Filtros -->
                            <div class="filters-container">
                                <div class="search-container">
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        class="search-input"
                                        placeholder="Buscar por nombre de facultad..."
                                    />
                                </div>
                                <div class="municipality-filter">
                                    <select
                                        v-model="selectedMunicipality"
                                        class="municipality-select"
                                    >
                                        <option value="">Todos los municipios</option>
                                        <option v-for="municipality in municipalities" :value="municipality.id" :key="municipality.id">
                                            {{ municipality.name }}
                                        </option>
                                    </select>
                                </div>
                                <button class="reset-btn" @click="resetFilters">
                                    Restablecer Filtros
                                </button>
                            </div>

                            <!-- Grid de facultades -->
                            <div class="faculties-grid">
                                <div
                                    v-for="faculty in filteredFaculties"
                                    :key="faculty.id"
                                    class="faculty-card"
                                >
                                    <div class="faculty-header">
                                        <div class="faculty-icon">🏛️</div>
                                        <div>
                                            <div class="faculty-title">{{ faculty.name }}</div>
                                            <div class="faculty-subtitle">{{ faculty.location }}</div>
                                        </div>
                                    </div>

                                    <div v-if="faculty.image" class="faculty-image">
                                        <img :src="faculty.image" alt="Faculty Image" />
                                    </div>

                                    <div class="faculty-stats">
                                        <div class="stat">
                                            <span class="stat-number">{{ faculty.classrooms.length }}</span>
                                            <span class="stat-label">Aulas</span>
                                        </div>
                                    </div>

                                    <div class="faculty-description">
                                        <p><strong>Responsable:</strong> {{ faculty.responsible }}</p>
                                        <p><strong>Correo:</strong> {{ faculty.email }}</p>
                                        <p><strong>Teléfono:</strong> {{ faculty.phone }}</p>
                                        <p><strong>Servicios:</strong> {{ faculty.services }}</p>
                                        <p v-if="faculty.web_site">
                                            <strong>Sitio Web:</strong> 
                                            <a :href="faculty.web_site" target="_blank" class="web-link">{{ faculty.web_site }}</a>
                                        </p>
                                    </div>

                                    <button
                                        class="expand-btn"
                                        @click="showClassrooms(faculty)"
                                    >
                                        Ver Aulas
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de lista de aulas -->
        <div v-if="showClassroomsList" class="modal-overlay" @click="closeModals">
            <div class="modal-container classrooms-list-modal" @click.stop>
                <div class="modal-header">
                    <h3 class="modal-title">
                        Aulas de {{ selectedFaculty?.name }}
                    </h3>
                    <button class="close-btn" @click="closeModals">
                        ✕
                    </button>
                </div>
                <div class="modal-content">
                    <div class="classrooms-list">
                        <div 
                            v-for="classroom in selectedFaculty?.classrooms"
                            :key="classroom.id"
                            class="classroom-item"
                            @click="showClassroomDetails(classroom)"
                        >
                            <div class="classroom-item-content">
                                <div class="classroom-item-name">{{ classroom.name }}</div>
                                <div class="classroom-item-info">
                                    👥 {{ classroom.capacity }} personas
                                </div>
                            </div>
                            <div class="classroom-arrow">→</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de detalle del aula -->
        <div v-if="showClassroomDetail" class="modal-overlay" @click="closeModals">
            <div class="modal-container classroom-detail-modal" @click.stop>
                <div class="modal-header">
                    <div class="header-navigation">
                        <button class="back-btn" @click="goBackToList">
                            ← Volver a la lista
                        </button>
                        <h3 class="modal-title">
                            {{ selectedClassroom?.name }}
                        </h3>
                    </div>
                    <button class="close-btn" @click="closeModals">
                        ✕
                    </button>
                </div>
                <div class="modal-content">
                    <div class="classroom-detail">
                        <div v-if="selectedClassroom?.image" class="classroom-detail-image">
                            <img :src="selectedClassroom.image" alt="Classroom Image" />
                        </div>
                        
                        <div class="classroom-detail-info">
                            <div class="info-section">
                                <h4 class="section-title">Información General</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-icon">👥</span>
                                        <span class="info-label">Capacidad:</span>
                                        <span class="info-value">{{ selectedClassroom?.capacity }} personas</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon">🏛️</span>
                                        <span class="info-label">Facultad:</span>
                                        <span class="info-value">{{ selectedClassroom?.faculty_name }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon">📍</span>
                                        <span class="info-label">Ubicación:</span>
                                        <span class="info-value">{{ selectedClassroom?.faculty_location }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="info-section">
                                <h4 class="section-title">Contacto y Responsable</h4>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span class="info-icon">👤</span>
                                        <span class="info-label">Responsable:</span>
                                        <span class="info-value">{{ selectedClassroom?.responsible }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon">📧</span>
                                        <span class="info-label">Email:</span>
                                        <span class="info-value">{{ selectedClassroom?.email }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon">📞</span>
                                        <span class="info-label">Teléfono:</span>
                                        <span class="info-value">{{ selectedClassroom?.phone }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="info-section">
                                <h4 class="section-title">Servicios Disponibles</h4>
                                <div class="services-container">
                                    <div class="services-text">
                                        <span class="info-icon">📋</span>
                                        {{ selectedClassroom?.services }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DynamicLayout>
    <Leyenda></Leyenda>
</template>

<style scoped>
.main-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.filters-container {
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 30px;
    align-items: center;
}

.search-container {
    flex: 1;
    min-width: 200px;
}

.municipality-filter {
    flex: 1;
    min-width: 200px;
}

.municipality-select {
    width: 100%;
    padding: 15px;
    border-radius: 25px;
    border: 2px solid #64ffda;
    background: rgba(255, 255, 255, 0.1);
    color: #e0e6ed;
    font-size: 16px;
    appearance: none;
}

.municipality-select:focus {
    outline: none;
    box-shadow: 0 0 10px rgba(100, 255, 218, 0.5);
}

.municipality-select option {
    background-color: #1e293b;
    color: #ffffff;
    padding: 10px;
}

.municipality-select optgroup {
    background-color: #1e293b;
    color: #ffffff;
}

.reset-btn {
    background: linear-gradient(45deg, #ff6b6b, #ff8e53);
    color: #fff;
    border: none;
    padding: 12px 20px;
    border-radius: 25px;
    cursor: pointer;
    font-weight: bold;
    font-size: 1rem;
    transition: transform 0.3s ease;
}

.reset-btn:hover {
    transform: scale(1.05);
}

.search-input {
    width: 100%;
    padding: 15px;
    border-radius: 25px;
    border: 2px solid #64ffda;
    background: rgba(255, 255, 255, 0.1);
    color: #e0e6ed;
    font-size: 16px;
}

.search-input:focus {
    outline: none;
    box-shadow: 0 0 10px rgba(100, 255, 218, 0.5);
}

.faculties-grid {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.faculty-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(100, 255, 218, 0.2);
    border-radius: 20px;
    padding: 30px;
    transition: transform 0.3s ease;
}

.faculty-card:hover {
    transform: translateY(-5px);
}

.faculty-header {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.faculty-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(45deg, #64ffda, #3f51b5);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.faculty-title {
    font-size: 1.5rem;
    font-weight: bold;
    color: #64ffda;
}

.faculty-subtitle {
    color: #a8b2d1;
    font-size: 1rem;
}

.faculty-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 15px;
    margin: 20px 0;
}

.faculty-stats {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin: 20px 0;
}

.stat {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: bold;
    color: #64ffda;
}

.stat-label {
    font-size: 0.9rem;
    color: #a8b2d1;
}

.faculty-description {
    color: #ccd6f6;
    margin: 20px 0;
    line-height: 1.8;
}

.web-link {
    color: #64ffda;
    text-decoration: none;
}

.web-link:hover {
    text-decoration: underline;
}

.expand-btn {
    background: linear-gradient(45deg, #64ffda, #3f51b5);
    color: #000;
    border: none;
    padding: 12px 30px;
    border-radius: 25px;
    cursor: pointer;
    font-weight: bold;
    font-size: 1rem;
    transition: transform 0.3s ease;
}

.expand-btn:hover {
    transform: scale(1.05);
}

/* Estilos del Modal */
/* 1. REDUCIR EL DIFUMINADO DEL FONDO - Cambia la opacidad de 0.95 a 0.8 o 0.85 */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(17, 24, 39, 0.8); /* CAMBIO: de 0.95 a 0.8 para menos difuminado */
    display: flex;
    justify-content: flex-end;
    align-items: center;
    z-index: 1000;
    padding: 20px;
}

/* 2. FONDOS OSCUROS PARA LOS MODALES */
.modal-container {
    background: #1e293b; /* CAMBIO: fondo más oscuro (igual al contenido principal) */
    border-radius: 20px;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(100, 255, 218, 0.2);
    border: 1px solid rgba(100, 255, 218, 0.3);
    max-height: 90vh;
    overflow: hidden;
    animation: slideInRight 0.3s ease-out;
}

.classrooms-list-modal {
    width: 400px;
    margin-right: 0;
}

.classroom-detail-modal {
    width: 600px;
    margin-right: 0;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid rgba(100, 255, 218, 0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(30, 41, 59, 0.9); /* CAMBIO: fondo oscuro en lugar del celeste claro */
    backdrop-filter: blur(10px);
}

.header-navigation {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: bold;
    color: #64ffda;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.back-btn {
    background: none;
    border: none;
    color: #a8b2d1;
    cursor: pointer;
    font-size: 0.9rem;
    padding: 0;
    transition: color 0.3s ease;
}

.back-btn:hover {
    color: #64ffda;
}

.close-btn {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #ff6b6b;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 8px;
    border-radius: 10px;
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    font-weight: bold;
}

.close-btn:hover {
    background: rgba(239, 68, 68, 0.2);
    transform: scale(1.05);
}

.modal-content {
    padding: 20px;
    overflow-y: auto;
    max-height: calc(90vh - 80px);
    background: #1e293b; /* CAMBIO: fondo oscuro para el contenido */
    color: #e2e8f0; /* CAMBIO: texto claro */
}

/* Lista de Aulas */
.classrooms-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.classroom-item {
    background: rgba(30, 41, 59, 0.8); /* CAMBIO: fondo más oscuro */
    border: 1px solid rgba(100, 255, 218, 0.2);
    border-radius: 15px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.classroom-item:hover {
    background: rgba(100, 255, 218, 0.15); /* CAMBIO: hover más sutil */
    border-color: rgba(100, 255, 218, 0.4);
    transform: translateX(5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.classroom-item-content {
    flex: 1;
    background: transparent; /* CAMBIO: quitar el fondo celeste */
}

.classroom-item-name {
    font-weight: bold;
    color: #f1f5f9; /* CAMBIO: texto más claro */
    font-size: 1.1rem;
    margin-bottom: 5px;
}

.classroom-item-info {
    color: #cbd5e1; /* CAMBIO: texto más claro */
    font-size: 0.9rem;
}

.classroom-arrow {
    color: #64ffda;
    font-size: 1.2rem;
    transition: transform 0.3s ease;
    font-weight: bold;
}

.classroom-item:hover .classroom-arrow {
    transform: translateX(5px);
}

/* Detalle del Aula */
.classroom-detail {
    color: #e2e8f0; /* CAMBIO: texto más claro para mejor contraste */
}

.classroom-detail-image {
    margin-bottom: 30px;
}

.classroom-detail-image img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
}

.classroom-detail-info {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.info-section {
    background: rgba(30, 41, 59, 0.6); /* CAMBIO: fondo más oscuro */
    border-radius: 15px;
    padding: 20px;
    border: 1px solid rgba(100, 255, 218, 0.2);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
}
.section-title {
    color: #64ffda;
    font-size: 1.2rem;
    font-weight: bold;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.info-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
}

.info-icon {
    font-size: 1.2rem;
    width: 25px;
    text-align: center;
}

.info-label {
    color: #a8b2d1;
    font-weight: 600;
    min-width: 100px;
}

.info-value {
    color: #f1f5f9; /* CAMBIO: valores más claros */
    font-weight: 500;
}

.services-container {
    background: rgba(30, 41, 59, 0.8); /* CAMBIO: fondo más oscuro */
    border-radius: 10px;
    padding: 15px;
    border: 1px solid rgba(100, 255, 218, 0.2);
}

.services-text {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    color: #f1f5f9; /* CAMBIO: texto más claro */
    line-height: 1.6;
    font-weight: 500;
}

@media (max-width: 768px) {
    .main-container {
        padding: 15px;
    }
    
    .filters-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .faculty-card {
        padding: 20px;
    }
    
    .faculty-stats {
        gap: 20px;
    }

    .modal-overlay {
        padding: 0;
        justify-content: center;
    }

    .classrooms-list-modal,
    .classroom-detail-modal {
        width: 100vw;
        height: 100vh;
        border-radius: 0;
        margin: 0;
    }

    .modal-content {
        max-height: calc(100vh - 80px);
    }
}
</style>