<script setup>
defineProps({
    maxHeight: {
        type: String,
        default: '600px'
    },
    width: {
        type: String,
        default: '100%'
    }
});
</script>

<template>
    <div class="scrollbar-container">
        <div 
            class="scrollbar-content overflow-x-auto"
            :style="{ maxHeight, width }"
        >
            <slot />
        </div>
    </div>
</template>

<style scoped>
.scrollbar-content {
    overflow-y: auto;
}

.scrollbar-content::-webkit-scrollbar {
    width: 8px;
}

.scrollbar-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.scrollbar-content::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.scrollbar-content::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Para compatibilidad con Firefox */
.scrollbar-content {
    scrollbar-width: thin;
    scrollbar-color: #888 #f1f1f1;
}
</style>