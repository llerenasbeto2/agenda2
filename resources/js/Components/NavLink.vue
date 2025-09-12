<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
    ? 'inline-flex items-center px-1 pt-1 border-2 border-indigo-600 text-lg font-medium leading-5 text-white bg-indigo-900/50 focus:outline-none focus:text-white transition duration-150 ease-in-out rounded'
    : 'inline-flex items-center px-1 pt-1 border-2 border-transparent text-lg font-medium leading-5 text-white hover:bg-gray-700 hover:border-gray-600 focus:outline-none focus:text-white transition duration-150 ease-in-out rounded'
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
