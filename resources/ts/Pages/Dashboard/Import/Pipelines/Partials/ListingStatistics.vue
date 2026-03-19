<!--
  ListingStatistics Component

  Displays pipeline count per status in a compact card layout.
-->

<script setup lang="ts">
import { computed } from 'vue'
import {
  CheckCircle2,
  Circle,
  Settings,
  LayoutList,
} from 'lucide-vue-next'
import { PipelineStatsViewModel } from '@/types/generated'

interface Props {
  stats: PipelineStatsViewModel
}

const props = defineProps<Props>()

const statsCards = computed(() => [
  {
    title: 'Total',
    value: props.stats.total,
    icon: LayoutList,
    color: 'text-slate-600 dark:text-slate-400',
    bg: 'bg-slate-100 dark:bg-slate-800/50',
  },
  {
    title: 'Active',
    value: props.stats.active,
    icon: CheckCircle2,
    color: 'text-green-600 dark:text-green-400',
    bg: 'bg-green-100 dark:bg-green-900/30',
    pulse: true,
  },
  {
    title: 'Inactive',
    value: props.stats.inactive,
    icon: Circle,
    color: 'text-gray-500 dark:text-gray-400',
    bg: 'bg-gray-100 dark:bg-gray-800/50',
  },
  {
    title: 'Needs Config',
    value: props.stats.needsConfiguration,
    icon: Settings,
    color: 'text-yellow-600 dark:text-yellow-400',
    bg: 'bg-yellow-100 dark:bg-yellow-900/30',
  },
])
</script>

<template>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <div
      v-for="stat in statsCards"
      :key="stat.title"
      class="flex items-center gap-3 rounded-lg border bg-card px-4 py-3 transition-colors hover:bg-accent/50"
    >
      <div :class="['flex items-center justify-center rounded-md p-2', stat.bg]">
        <component
          :is="stat.icon"
          :class="['h-4 w-4', stat.color]"
        />
      </div>
      <div class="min-w-0">
        <p class="text-xs text-muted-foreground truncate">{{ stat.title }}</p>
        <p class="text-lg font-bold leading-tight">{{ stat.value }}</p>
      </div>
    </div>
  </div>
</template>
