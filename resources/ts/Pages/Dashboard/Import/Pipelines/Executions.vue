<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow, TableEmpty } from '@/components/ui/table'
import { Pagination } from '@/components/ui/pagination'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  ArrowLeft,
  CheckCircle2,
  XCircle,
  Clock,
  AlertCircle,
  Ban,
  Activity,
  RefreshCw,
  Eye,
  Info,
  Play,
  Database
} from 'lucide-vue-next'
import { PipelineViewModel } from '@/types/generated'
import { format, formatDistance } from 'date-fns'
import { ref, computed } from 'vue'

interface Execution {
  id: number
  status: 'pending' | 'running' | 'completed' | 'failed' | 'cancelled'
  startedAt: string | null
  completedAt: string | null
  totalRows: number
  processedRows: number
  successRate: number | string | null
  processingTime: number | string | null
  memoryUsage: number | string | null
  errorMessage: string | null
  createdAt: string
}

interface Props {
  pipeline: PipelineViewModel
  executions: Execution[]
  paginator: {
    currentPage: number
    lastPage: number
    perPage: number
    total: number
    from: number | null
    to: number | null
  }
}

const props = defineProps<Props>()

const truncateText = (text: string, maxLength: number = 50): string => {
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

const openErrorDialog = ref<number | null>(null)

const getStatusBadge = (execution: Execution) => {
  const statusConfig = {
    pending: {
      variant: 'secondary' as const,
      text: 'Pending',
      icon: Clock,
      class: 'bg-gray-500/10 text-gray-700 dark:text-gray-400 border-gray-500/20'
    },
    running: {
      variant: 'default' as const,
      text: 'Running',
      icon: Activity,
      class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20'
    },
    completed: {
      variant: 'default' as const,
      text: 'Completed',
      icon: CheckCircle2,
      class: 'bg-green-500/10 text-green-700 dark:text-green-400 border-green-500/20'
    },
    failed: {
      variant: 'destructive' as const,
      text: 'Failed',
      icon: XCircle,
      class: 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-500/20'
    },
    cancelled: {
      variant: 'secondary' as const,
      text: 'Cancelled',
      icon: Ban,
      class: 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border-yellow-500/20'
    }
  }

  return statusConfig[execution.status] || statusConfig.pending
}

const formatDuration = (seconds: number | string | null): string => {
  if (!seconds && seconds !== 0) return 'N/A'
  
  const numSeconds = typeof seconds === 'string' ? parseFloat(seconds) : seconds
  
  if (isNaN(numSeconds) || numSeconds < 0) return 'N/A'
  
  if (numSeconds < 60) {
    return `${numSeconds.toFixed(2)}s`
  }
  
  const minutes = Math.floor(numSeconds / 60)
  const remainingSeconds = numSeconds % 60
  
  if (minutes < 60) {
    return `${minutes}m ${remainingSeconds.toFixed(0)}s`
  }
  
  const hours = Math.floor(minutes / 60)
  const remainingMinutes = minutes % 60
  
  return `${hours}h ${remainingMinutes}m`
}

const formatMemory = (bytes: number | string | null): string => {
  if (!bytes && bytes !== 0) return 'N/A'
  
  const numBytes = typeof bytes === 'string' ? parseFloat(bytes) : bytes
  
  if (isNaN(numBytes) || numBytes < 0) return 'N/A'
  
  const kb = numBytes / 1024
  const mb = kb / 1024
  const gb = mb / 1024
  
  if (gb >= 1) {
    return `${gb.toFixed(2)} GB`
  }
  if (mb >= 1) {
    return `${mb.toFixed(2)} MB`
  }
  return `${kb.toFixed(2)} KB`
}

const getSuccessRate = (rate: number | string | null): number | null => {
  if (rate === null || rate === undefined) return null
  const numRate = typeof rate === 'string' ? parseFloat(rate) : rate
  return isNaN(numRate) ? null : numRate
}

const refresh = () => {
  const currentRoute = route().current()
  const currentParams = route().params
  
  if (currentRoute) {
    router.visit(route(currentRoute, currentParams), {
      only: ['executions', 'paginator'],
      preserveScroll: true,
    })
  } else {
    router.reload({
      only: ['executions', 'paginator'],
    })
  }
}

const processNow = () => {
  router.post(route('dashboard.import.pipelines.process-now', { pipeline: props.pipeline.id }), {}, {
    preserveScroll: true,
  })
}

const isActive = computed(() => props.pipeline.isActive)
</script>

<template>
  <Head :title="`${props.pipeline.name} - Execution History`" />
  
  <Default>
    <!-- Page Header -->
    <PageHeader 
      :title="`Execution History: ${props.pipeline.name}`"
      :description="`View execution history and performance metrics for this pipeline`"
    >
      <template #actions>
        <Button variant="default" size="sm" @click="processNow" :disabled="!isActive">
          <Play class="w-4 h-4 mr-2" />
          Process Now
        </Button>
        <Button variant="outline" size="sm" @click="refresh">
          <RefreshCw class="w-4 h-4 mr-2" />
          Refresh
        </Button>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.import.pipelines.show', { pipeline: props.pipeline.id })">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Pipeline
          </Link>
        </Button>
      </template>
    </PageHeader>

    <!-- Executions Table -->
    <Card class="shadow-sm w-full">
      <CardHeader class="pb-4 border-b">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="space-y-1">
            <CardTitle class="text-xl font-bold">Executions</CardTitle>
            <CardDescription class="text-sm">
              A list of all execution runs for this pipeline
            </CardDescription>
          </div>
          <div class="text-sm text-muted-foreground">
            Total: {{ paginator.total }} {{ paginator.total === 1 ? 'execution' : 'executions' }}
          </div>
        </div>
      </CardHeader>
      <CardContent class="pt-6">
        <div class="overflow-x-auto rounded-lg border bg-background" style="max-width: 100%;">
          <Table class="min-w-full">
            <TableHeader>
              <TableRow>
                <TableHead class="w-[80px]">ID</TableHead>
                <TableHead class="w-[120px]">Status</TableHead>
                <TableHead class="w-[220px]">Started At</TableHead>
                <TableHead class="w-[220px]">Completed At</TableHead>
                <TableHead class="w-[140px]">Rows</TableHead>
                <TableHead class="w-[120px]">Success Rate</TableHead>
                <TableHead class="w-[160px]">Performance</TableHead>
                <TableHead class="w-[250px]">Error</TableHead>
                <TableHead class="w-[140px] text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableEmpty v-if="executions.length === 0" :colspan="9">
                <div class="text-center py-16 px-4">
                  <div class="mx-auto w-20 h-20 rounded-full bg-muted flex items-center justify-center mb-6">
                    <Activity class="h-10 w-10 text-muted-foreground" />
                  </div>
                  <h3 class="mt-2 text-xl font-bold">No executions yet</h3>
                  <p class="mt-2 text-sm text-muted-foreground max-w-md mx-auto">
                    This pipeline hasn't been executed yet. Run the pipeline to see execution history here.
                  </p>
                </div>
              </TableEmpty>
              <TableRow 
                v-for="execution in executions" 
                :key="execution.id"
                class="hover:bg-muted/50 transition-colors"
              >
                <TableCell class="text-sm text-muted-foreground font-mono whitespace-nowrap w-[80px]">
                  <Link 
                    :href="route('dashboard.import.pipelines.executions.show', { pipeline: props.pipeline.id, execution: execution.id })"
                    class="hover:text-primary hover:underline transition-colors"
                  >
                    #{{ execution.id }}
                  </Link>
                </TableCell>
                <TableCell class="whitespace-nowrap w-[120px]">
                  <Badge 
                    :variant="getStatusBadge(execution).variant"
                    :class="getStatusBadge(execution).class"
                    class="flex items-center gap-1.5 font-medium w-fit"
                  >
                    <component :is="getStatusBadge(execution).icon" class="h-3 w-3" />
                    {{ getStatusBadge(execution).text }}
                  </Badge>
                </TableCell>
                <TableCell class="text-sm w-[220px]">
                  <div v-if="execution.startedAt" class="space-y-0.5">
                    <div class="whitespace-nowrap">{{ format(new Date(execution.startedAt), 'MMM dd, yyyy HH:mm:ss') }}</div>
                    <div class="text-xs text-muted-foreground whitespace-nowrap">
                      {{ formatDistance(new Date(execution.startedAt), new Date(), { addSuffix: true }) }}
                    </div>
                  </div>
                  <span v-else class="text-muted-foreground whitespace-nowrap">Not started</span>
                </TableCell>
                <TableCell class="text-sm w-[220px]">
                  <div v-if="execution.completedAt" class="space-y-0.5">
                    <div class="whitespace-nowrap">{{ format(new Date(execution.completedAt), 'MMM dd, yyyy HH:mm:ss') }}</div>
                    <div class="text-xs text-muted-foreground whitespace-nowrap">
                      {{ formatDistance(new Date(execution.completedAt), new Date(), { addSuffix: true }) }}
                    </div>
                  </div>
                  <span v-else class="text-muted-foreground whitespace-nowrap">In progress</span>
                </TableCell>
                <TableCell class="text-sm whitespace-nowrap w-[140px]">
                  <div v-if="execution.totalRows > 0" class="space-y-0.5">
                    <div class="font-medium">{{ execution.processedRows.toLocaleString() }} / {{ execution.totalRows.toLocaleString() }}</div>
                    <div class="text-xs text-muted-foreground">
                      {{ execution.processedRows > 0 ? Math.round((execution.processedRows / execution.totalRows) * 100) : 0 }}% processed
                    </div>
                  </div>
                  <span v-else class="text-muted-foreground">-</span>
                </TableCell>
                <TableCell class="text-sm whitespace-nowrap w-[120px]">
                  <div v-if="getSuccessRate(execution.successRate) !== null" class="space-y-0.5">
                    <div class="font-medium" :class="(getSuccessRate(execution.successRate) || 0) >= 90 ? 'text-green-600' : (getSuccessRate(execution.successRate) || 0) >= 70 ? 'text-yellow-600' : 'text-red-600'">
                      {{ (getSuccessRate(execution.successRate) || 0).toFixed(2) }}%
                    </div>
                  </div>
                  <span v-else class="text-muted-foreground">-</span>
                </TableCell>
                <TableCell class="text-sm whitespace-nowrap w-[160px]">
                  <div class="space-y-1">
                    <div v-if="execution.processingTime" class="text-xs">
                      <span class="text-muted-foreground">Time:</span> {{ formatDuration(execution.processingTime) }}
                    </div>
                    <div v-if="execution.memoryUsage" class="text-xs">
                      <span class="text-muted-foreground">Memory:</span> {{ formatMemory(execution.memoryUsage) }}
                    </div>
                    <span v-if="!execution.processingTime && !execution.memoryUsage" class="text-muted-foreground text-xs">-</span>
                  </div>
                </TableCell>
                <TableCell class="text-sm w-[250px]" @click.stop>
                  <div v-if="execution.errorMessage" class="flex items-center gap-2 text-red-600">
                    <button 
                      class="flex-shrink-0 hover:opacity-70 transition-opacity p-0.5" 
                      @click="openErrorDialog = execution.id"
                    >
                      <Info class="w-4 h-4" />
                    </button>
                    <span class="truncate">{{ truncateText(execution.errorMessage, 50) }}</span>
                    <Dialog :open="openErrorDialog === execution.id" @update:open="(val) => openErrorDialog = val ? execution.id : null">
                      <DialogContent class="max-w-2xl max-h-[80vh] flex flex-col">
                        <DialogHeader>
                          <DialogTitle>Error Message</DialogTitle>
                          <DialogDescription>
                            Full error details for execution #{{ execution.id }}
                          </DialogDescription>
                        </DialogHeader>
                        <div class="mt-4 flex-1 overflow-hidden flex flex-col">
                          <div class="p-4 bg-red-50 dark:bg-red-950/20 rounded-lg border border-red-200 dark:border-red-900 flex-1 overflow-hidden flex flex-col">
                            <div class="flex items-start gap-3 flex-shrink-0 mb-2">
                              <AlertCircle class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                              <div class="font-medium text-red-900 dark:text-red-100">Error Details</div>
                            </div>
                            <div class="overflow-y-auto flex-1">
                              <div class="text-sm text-red-800 dark:text-red-200 whitespace-pre-wrap break-words pr-2">{{ execution.errorMessage }}</div>
                            </div>
                          </div>
                        </div>
                      </DialogContent>
                    </Dialog>
                  </div>
                  <span v-else class="text-muted-foreground">-</span>
                </TableCell>
                <TableCell class="w-[140px] text-right" @click.stop>
                  <div class="flex items-center justify-end gap-1">
                    <Button variant="ghost" size="icon" :title="'View Data'" as-child>
                      <Link :href="route('dashboard.import.pipelines.executions.results', { pipeline: props.pipeline.id, execution: execution.id })">
                        <Database class="w-4 h-4" />
                        <span class="sr-only">View Results</span>
                      </Link>
                    </Button>
                    <Button variant="ghost" size="icon" :title="'View Details'" as-child>
                      <Link :href="route('dashboard.import.pipelines.executions.show', { pipeline: props.pipeline.id, execution: execution.id })">
                        <Eye class="w-4 h-4" />
                        <span class="sr-only">View details</span>
                      </Link>
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Pagination -->
        <div v-if="paginator.total > paginator.perPage" class="mt-6 pt-4 border-t">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-sm text-muted-foreground">
              Showing 
              <span class="font-semibold text-foreground">{{ paginator.from || 0 }}</span> 
              to 
              <span class="font-semibold text-foreground">{{ paginator.to || 0 }}</span> 
              of 
              <span class="font-semibold text-foreground">{{ paginator.total }}</span> 
              {{ paginator.total === 1 ? 'execution' : 'executions' }}
            </div>
            <Pagination :paginator="paginator" />
          </div>
        </div>
      </CardContent>
    </Card>
  </Default>
</template>

