<script setup>
import DynamicLayout from '@/Layouts/DynamicLayout.vue';
import { Head } from '@inertiajs/vue3';
import Scrollbar from '@/Components/Scrollbar.vue';
import { ref, computed } from 'vue';
import FilterBar from '@/Components/Reservations/FilterBar.vue';
import ReservationsTable from '@/Components/Reservations/ReservationsTable.vue';
//import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    reservations_classrooms: Array,
    recurring_patterns: Array
});

const searchQuery = ref('');
const selectedStatus = ref('Todos');

const filteredReservations = computed(() => {
    let filtered = [...props.reservations_classrooms || []];
    
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(reservation => 
            reservation.event_title?.toLowerCase().includes(query) || 
            reservation.full_name?.toLowerCase().includes(query) ||
            (reservation.classroom?.name || '').toLowerCase().includes(query)
        );
    }
    
    if (selectedStatus.value !== 'Todos') {
        filtered = filtered.filter(reservation => reservation.status === selectedStatus.value);
    }
    
    return filtered;
});

</script>

<template>
    <Head title="Mis Reservaciones" />
    <DynamicLayout>
        <template #header>
            <PageHeader title="Mis Reservaciones" />
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-lg sm:rounded-lg dark:bg-gray-800 border border-teal-100 dark:border-teal-900">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <FilterBar
                            v-model:searchQuery="searchQuery"
                            v-model:selectedStatus="selectedStatus"
                        />
                        <Scrollbar max-height="600px">
                            <ReservationsTable :reservations="filteredReservations" />
                        </Scrollbar>
                    </div>
                </div>
            </div>
        </div>
    </DynamicLayout>
</template>