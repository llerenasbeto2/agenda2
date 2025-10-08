<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
  itemsPerPage: {
    type: Number,
    default: 5,
  },
});

const emit = defineEmits(['update:currentPage', 'update:itemsPerPage', 'page-changed']);

const currentPage = ref(1);
const localItemsPerPage = ref(props.itemsPerPage);

const perPageOptions = [5, 10, 25, 50];

const onItemsPerPageChange = () => {
  emit('update:itemsPerPage', localItemsPerPage.value);
  goToPage(1);
};

// Compute total pages
const totalPages = computed(() => {
  return Math.ceil(props.items.length / localItemsPerPage.value);
});

// Compute paginated items for the current page
const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * localItemsPerPage.value;
  const end = start + localItemsPerPage.value;
  return props.items.slice(start, end);
});

// Navigate to a specific page
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
    emit('update:currentPage', page);
    emit('page-changed', paginatedItems.value);
  }
};

// Previous page
const prevPage = () => {
  if (currentPage.value > 1) {
    goToPage(currentPage.value - 1);
  }
};

// Next page
const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    goToPage(currentPage.value + 1);
  }
};

// Generate page numbers for display (e.g., 1, 2, 3, ..., last)
const pageNumbers = computed(() => {
  const pages = [];
  const maxVisiblePages = 5; // Show up to 5 page numbers at a time
  let startPage = Math.max(1, currentPage.value - Math.floor(maxVisiblePages / 2));
  let endPage = Math.min(totalPages.value, startPage + maxVisiblePages - 1);

  // Adjust startPage if endPage is at the max
  if (endPage - startPage + 1 < maxVisiblePages) {
    startPage = Math.max(1, endPage - maxVisiblePages + 1);
  }

  for (let i = startPage; i <= endPage; i++) {
    pages.push(i);
  }

  return pages;
});

// Watch for changes in totalPages to reset to page 1 if needed
watch(totalPages, (newVal) => {
  if (currentPage.value > newVal) {
    goToPage(1);
  }
});

// Sync localItemsPerPage with props if parent changes it
watch(() => props.itemsPerPage, (newVal) => {
  localItemsPerPage.value = newVal;
});
</script>

<template>
  <div>
    <!-- Pass paginated items to the parent via slot -->
    <slot :paginatedItems="paginatedItems"></slot>

    <!-- Pagination controls -->
    <div v-if="props.items.length > 0" class="mt-4 flex items-center">
      <div class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-200 mr-4">
        <span>Mostrar</span>
        <select 
          v-model="localItemsPerPage" 
          @change="onItemsPerPageChange"
          class="text-sm px-2 py-1 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option 
            v-for="option in perPageOptions" 
            :key="option" 
            :value="option"
          >
            {{ option }}
          </option>
        </select>
        <span>por página</span>
      </div>

      <div class="flex-1 flex justify-center">
        <div v-if="totalPages > 1" class="flex items-center space-x-2">
          <button
            @click="prevPage"
            :disabled="currentPage === 1"
            class="px-3 py-1 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 disabled:opacity-50"
          >
            Anterior
          </button>

          <button
            v-for="page in pageNumbers"
            :key="page"
            @click="goToPage(page)"
            :class="[
              'px-3 py-1 rounded-md border',
              currentPage === page
                ? 'bg-blue-500 text-white border-blue-500'
                : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600',
            ]"
          >
            {{ page }}
          </button>

          <button
            @click="nextPage"
            :disabled="currentPage === totalPages"
            class="px-3 py-1 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 disabled:opacity-50"
          >
            Siguiente
          </button>
        </div>
      </div>
    </div>
  </div>
</template>