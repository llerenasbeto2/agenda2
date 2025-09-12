<script setup>
defineProps({
  modelValue: Boolean,
  form: {
    type: Object,
    required: true
  }
});

defineEmits(['update:modelValue', 'update:form']);
</script>