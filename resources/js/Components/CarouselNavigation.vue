<template>
    <div class="carousel-navigation">
      <button @click="goToPrevious" :disabled="currentIndex === 0">Anterior</button>
      <button @click="goToNext" :disabled="currentIndex === totalItems - 1">Siguiente</button>
    </div>
  </template>
  
  <script>
  export default {
    props: {
      currentIndex: {
        type: Number,
        required: true
      },
      totalItems: {
        type: Number,
        required: true
      }
    },
    methods: {
      goToPrevious() {
        if (this.currentIndex > 0) {
          this.$emit('updateIndex', this.currentIndex - 1);
        }
      },
      goToNext() {
        if (this.currentIndex < this.totalItems - 1) {
          this.$emit('updateIndex', this.currentIndex + 1);
        }
      }
    }
  };
  </script>
  
  <style scoped>
  .carousel-navigation {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
  }
  
  button {
    padding: 10px;
    font-size: 16px;
    cursor: pointer;
  }
  
  button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
  }
  </style>
  