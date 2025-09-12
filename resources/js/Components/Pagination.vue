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

const emit = defineEmits(['update:currentPage', 'page-changed']);

const currentPage = ref(1);

// Compute total pages
const totalPages = computed(() => {
  return Math.ceil(props.items.length / props.itemsPerPage);
});

// Compute paginated items for the current page
const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * props.itemsPerPage;
  const end = start + props.itemsPerPage;
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

// Watch for changes in items to reset to page 1 if needed
watch(() => props.items, () => {
  if (currentPage.value > totalPages.value) {
    goToPage(1);
  }
});
</script>

<template>
  <div>
    <!-- Pass paginated items to the parent via slot -->
    <slot :paginatedItems="paginatedItems"></slot>

    <!-- Pagination controls -->
    <div v-if="totalPages > 1" class="mt-4 flex justify-center items-center space-x-2">
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
</template>