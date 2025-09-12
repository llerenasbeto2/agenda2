<script setup>
defineProps({
  modelValue: Array,
  form: {
    type: Object,
    required: true
  }
});

defineEmits(['update:modelValue']);
</script>