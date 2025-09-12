<template>
  <div class="unit-card" @click="$emit('select-faculty', { name, image, scenarios, ...$attrs })">
    <div class="image-container">
      <img :src="image" :alt="name" />
    </div>
    <div class="unit-info">
      <h3>{{ name }}</h3>
      <p>{{ scenarios }} Escenarios</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  name: String,
  image: String,
  scenarios: {
    type: Number,
    default: 0,
  },
});

defineEmits(['select-faculty']);
</script>

<style scoped>
.unit-card {
  background: rgba(255, 255, 255, 0.05); /* Fondo translúcido claro */
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1); /* Borde claro */
  border-radius: 15px;
  overflow: hidden;
  transition: transform 0.4s, box-shadow 0.4s;
  cursor: pointer;
}

/* Modo oscuro: fondo más oscuro y borde más visible */
.dark .unit-card {
  background: rgba(31, 41, 55, 0.8); /* Fondo gris oscuro translúcido */
  border: 1px solid rgba(255, 255, 255, 0.2); /* Borde más claro para contraste */
}

.unit-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 10px 25px rgba(0, 145, 255, 0.5); /* Sombra azul en modo claro */
}

/* Modo oscuro: sombra más suave para contraste */
.dark .unit-card:hover {
  box-shadow: 0 10px 25px rgba(0, 145, 255, 0.7); /* Sombra azul más intensa */
}

.image-container {
  height: 180px;
  overflow: hidden;
}

.image-container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s;
}

.unit-card:hover .image-container img {
  transform: scale(1.1);
}

.unit-info {
  padding: 10px;
  text-align: center;
}

.unit-info h3 {
  color: #00d1ff; /* Azul claro en modo claro */
  font-size: 1.0rem;
  font-weight: bold;
}

/* Modo oscuro: ajustar el color del título */
.dark .unit-info h3 {
  color: #4da8ff; /* Azul más claro para mejor contraste */
}

.unit-info p {
  color: #bbb; /* Gris claro en modo claro */
  margin-top: 5px;
}

/* Modo oscuro: texto más claro */
.dark .unit-info p {
  color: #d1d5db; /* Gris claro para modo oscuro */
}
</style>