<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <tr
    data-slot="table-row"
    :class="cn('hover:bg-muted/50 data-[state=selected]:bg-muted border-b transition-all duration-150 cursor-pointer', props.class)"
  >
    <slot />
  </tr>
</template>
