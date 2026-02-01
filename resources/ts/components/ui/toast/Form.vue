<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { provide } from 'vue';

const props = defineProps<{
  form: InertiaForm<any>;
}>();

const emit = defineEmits<{
  (e: 'submit', submitEvent: SubmitEvent): void;
}>();

// provide form to children
provide<InertiaForm<Record<string, any>>>('form', props.form);

const onSubmit = (event: Event) => {
  emit('submit', event as SubmitEvent);
};
</script>

<template>
  <form @submit.prevent="onSubmit">
    <slot />
  </form>
</template>
