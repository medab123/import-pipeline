<script lang="ts" setup>
import { useFormField } from './useFormField.ts';
import { computed } from 'vue';

const { getErrors } = useFormField();

const props = defineProps<{
  for: string;
}>();

const errors = computed(() => getErrors(props.for));
</script>

<template>
  <p
      v-if="errors"
      class="text-sm font-medium text-destructive mt-1.5 flex items-center gap-1.5 animate-in fade-in slide-in-from-top-1 duration-200"
  >
    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    {{ errors }}
  </p>
</template>