<!--
  ListingStatistics Component
  
  Dedicated statistics component for Import Pipelines listing page.
  Displays key pipeline metrics in a card layout.
-->

<script setup lang="ts">
import { computed } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { 
  Activity,
  CheckCircle,
  XCircle,
  Clock,
  TrendingUp
} from 'lucide-vue-next'
import { PipelineStatsViewModel } from '@/types/generated'

interface Props {
  stats: PipelineStatsViewModel
}

const props = defineProps<Props>()

// Computed properties for pipeline statistics
const statsCards = computed(() => [
  {
    title: 'Active Pipelines',
    value: props.stats.active,
    icon: Activity,
    description: 'Currently running',
    color: 'text-green-600',
    trend: props.stats.active > 0 ? { value: 12, isPositive: true } : undefined
  },
  {
    title: 'Successful',
    value: props.stats.successful,
    icon: CheckCircle,
    description: `Completed successfully (${props.stats.successRate}%)`,
    color: 'text-blue-600',
    trend: props.stats.successful > 0 ? { value: 8, isPositive: true } : undefined
  },
  {
    title: 'Failed',
    value: props.stats.failed,
    icon: XCircle,
    description: `Execution failed (${props.stats.failureRate}%)`,
    color: 'text-red-600',
    trend: props.stats.failed > 0 ? { value: -3, isPositive: false } : undefined
  },
  {
    title: 'Running',
    value: props.stats.running,
    icon: Clock,
    description: 'Currently executing',
    color: 'text-orange-600',
    trend: props.stats.running > 0 ? { value: 5, isPositive: true } : undefined
  }
])

const formatTrend = (trend: { value: number; isPositive: boolean }) => {
  const sign = trend.isPositive ? '+' : ''
  return `${sign}${trend.value}%`
}
</script>

<template>
  <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6">
    <Card 
      v-for="stat in statsCards" 
      :key="stat.title" 
      class="transition-all duration-300 hover:shadow-lg hover:-translate-y-1 border-2 hover:border-primary/20 group relative overflow-hidden"
    >
      <!-- Gradient background on hover -->
      <div class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none" />
      
      <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3 relative z-10">
        <CardTitle class="text-sm font-semibold text-muted-foreground">
          {{ stat.title }}
        </CardTitle>
        <div class="relative">
          <component 
            :is="stat.icon" 
            :class="['h-5 w-5 transition-all duration-300 group-hover:scale-110', stat.color]" 
          />
          <!-- Pulse effect for active/running stats -->
          <span
            v-if="stat.title === 'Active Pipelines' || stat.title === 'Running'"
            class="absolute inset-0 rounded-full bg-current animate-ping opacity-20"
          />
        </div>
      </CardHeader>
      <CardContent class="pt-0 relative z-10">
        <div class="text-3xl font-bold mb-3 group-hover:text-primary transition-colors duration-300">
          {{ stat.value }}
        </div>
        <div class="flex items-center justify-between">
          <p class="text-xs text-muted-foreground leading-relaxed">
            {{ stat.description }}
          </p>
          <div v-if="stat.trend" class="flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full bg-muted/50">
            <span 
              :class="[
                'transition-colors',
                stat.trend.isPositive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
              ]"
            >
              {{ formatTrend(stat.trend) }}
            </span>
            <TrendingUp 
              :class="[
                'h-3 w-3 transition-all duration-300',
                stat.trend.isPositive 
                  ? 'text-green-600 dark:text-green-400' 
                  : 'text-red-600 dark:text-red-400 rotate-180'
              ]"
            />
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>