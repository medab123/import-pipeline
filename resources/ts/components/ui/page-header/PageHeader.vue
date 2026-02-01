<script setup lang="ts">
interface Props {
  title: string
  description?: string
}

const props = withDefaults(defineProps<Props>(), {
  description: ''
})
</script>

<template>
  <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="space-y-1">
      <h1 class="text-3xl font-bold tracking-tight">{{ props.title }}</h1>
      <p v-if="props.description" class="text-sm text-muted-foreground max-w-2xl">
        {{ props.description }}
      </p>
    </div>
    <div v-if="$slots.actions" class="flex items-center gap-2 flex-shrink-0">
      <slot name="actions" />
    </div>
  </div>
</template>
