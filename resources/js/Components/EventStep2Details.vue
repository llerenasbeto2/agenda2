<template>
    <div class="bg-gray-50 p-4 rounded-lg">
      <h2 class="text-lg font-semibold mb-4 text-black">Información del Evento</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="form-group">
          <label for="eventTitle" class="block font-medium mb-1 text-black">Título del Evento</label>
          <input
            id="eventTitle"
            type="text"
            v-model="form.event_title"
            class="w-full px-3 py-2 border rounded-md"
            required
          />
          <p v-if="errors.event_title" class="text-red-500 text-sm mt-1">{{ errors.event_title }}</p>
        </div>
        <div class="form-group">
          <label for="categoryType" class="block font-medium mb-1 text-black">Categoría</label>
          <select
            id="categoryType"
            v-model="form.category_type"
            class="w-full px-3 py-2 border rounded-md"
            required
          >
            <option value="" disabled>Seleccione una categoría</option>
            <option
              v-for="c in formData.categories"
              :key="c.id"
              :value="c.id"
            >
              {{ c.name }}
            </option>
          </select>
          <p v-if="errors.category_type" class="text-red-500 text-sm mt-1">{{ errors.category_type }}</p>
        </div>
        <div class="form-group">
          <label for="faculty" class="block font-medium mb-1 text-black">Facultad</label>
          <select
            id="faculty"
            v-model="form.faculty_id"
            @change="onFacultyChange"
            class="w-full px-3 py-2 border rounded-md"
            required
          >
            <option value="" disabled>Seleccione una facultad</option>
            <option
              v-for="f in formData.faculties"
              :key="f.id"
              :value="f.id"
            >
              {{ f.name }}
            </option>
          </select>
          <p v-if="errors.faculty_id" class="text-red-500 text-sm mt-1 ">{{ errors.faculty_id }}</p>
        </div>
        <div class="form-group">
          <label for="classroom" class="block font-medium mb-1 text-black">Aula</label>
          <select
            id="classroom"
            v-model="form.classroom_id"
            class="w-full px-3 py-2 border rounded-md"
            :disabled="!form.faculty_id"
            required
          >
            <option value="" disabled>Seleccione un aula</option>
            <option
              v-for="r in classrooms"
              :key="r.id"
              :value="r.id"
            >
              {{ r.name }}
            </option>
          </select>
          <p v-if="errors.classroom_id" class="text-red-500 text-sm mt-1">{{ errors.classroom_id }}</p>
        </div>
        <div class="form-group">
          <label for="attendees" class="block font-medium mb-1 text-black">Número de Asistentes</label>
          <input
            id="attendees"
            type="number"
            v-model="form.attendees"
            min="1"
            class="w-full px-3 py-2 border rounded-md"
            required
          />
          <p v-if="errors.attendees" class="text-red-500 text-sm mt-1">{{ errors.attendees }}</p>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  const props = defineProps({
    form: Object,
    errors: Object,
    formData: Object,
    classrooms: Array
  });
  const emit = defineEmits(['load-classrooms']);
  
  function onFacultyChange() {
    emit('load-classrooms', props.form.faculty_id);
  }
  </script>
  