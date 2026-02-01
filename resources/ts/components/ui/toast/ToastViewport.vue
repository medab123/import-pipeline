<script setup lang="ts">
import type { ToastViewportProps } from 'radix-vue';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils.ts';
import { ToastViewport } from 'radix-vue';
import { computed } from 'vue';

const props = defineProps<ToastViewportProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const { class: _, ...delegated } = props;

  return delegated;
});
</script>

<template>
  <ToastViewport
    v-bind="delegatedProps"
    :class="cn('fixed top-0 right-0 z-[100] flex max-h-screen w-full flex-col p-4 md:max-w-[420px]', props.class)"
  />
</template>
