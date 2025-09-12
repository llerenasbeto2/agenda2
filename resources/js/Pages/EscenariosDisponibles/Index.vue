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
const expandedFaculties = ref({});
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

// Modificado: Solo busca por nombre de facultad
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

const toggleClassrooms = (facultyId) => {
    expandedFaculties.value[facultyId] = !expandedFaculties.value[facultyId];
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedMunicipality.value = '';
};

const updateImageUrl = () => {
    if (!imageUrlForm.image_url || !currentClassroom.value) return;
    imageUrlForm.post(route('classrooms.image-url.update', {
        classroom: currentClassroom.value.id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            imageUrlForm.reset();
            currentClassroom.value.image = imageUrlForm.image_url + (imageUrlForm.image_url.includes('?') ? '&' : '?') + 't=' + Date.now();
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
                                            <span class="stat-number">{{ faculty.capacity }}</span>
                                            <span class="stat-label">Aforo</span>
                                        </div>
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
                                        @click="toggleClassrooms(faculty.id)"
                                    >
                                        {{ expandedFaculties[faculty.id] ? 'Ocultar Aulas' : 'Ver Aulas' }}
                                    </button>

                                    <div class="classrooms-section" :class="{ expanded: expandedFaculties[faculty.id] }">
                                        <div class="classrooms-grid">
                                            <div
                                                v-for="classroom in faculty.classrooms"
                                                :key="classroom.id"
                                                class="classroom-card"
                                            >
                                                <div class="classroom-header">
                                                    <div class="classroom-name">{{ classroom.name }}</div>
                                                </div>

                                                <div v-if="classroom.image" class="classroom-image">
                                                    <img
                                                        :src="classroom.image"
                                                        alt="Classroom Image"
                                                    />
                                                </div>

                                                <div class="classroom-details">
                                                    <div>👥 Capacidad: {{ classroom.capacity }}</div>
                                                    <div>📋 Servicios: {{ classroom.services }}</div>
                                                    <div>👤 Responsable: {{ classroom.responsible }}</div>
                                                    <div>📧 Email: {{ classroom.email }}</div>
                                                    <div>📞 Teléfono: {{ classroom.phone }}</div>
                                                </div>
                                            </div>
                                        </div>
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

/* Estilo para las opciones del dropdown */
.municipality-select option {
    background-color: #1e293b; /* Fondo oscuro similar a tu diseño */
    color: #ffffff; /* Texto blanco */
    padding: 10px;
}

/* Asegurar que el texto seleccionado también sea legible */
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

.classrooms-section {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.5s ease;
    margin-top: 20px;
}

.classrooms-section.expanded {
    max-height: 2000px;
}

.classrooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.classroom-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(100, 255, 218, 0.1);
    border-radius: 15px;
    padding: 20px;
}

.classroom-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.classroom-name {
    font-weight: bold;
    color: #e0e6ed;
}

.classroom-image img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 15px;
}

.classroom-details {
    color: #a8b2d1;
    font-size: 0.9rem;
    line-height: 1.6;
}

.classroom-details div {
    margin-bottom: 5px;
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
    
    .classrooms-grid {
        grid-template-columns: 1fr;
    }
}
</style>