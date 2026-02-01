<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible'
import { 
  ArrowLeft,
  CheckCircle2,
  XCircle,
  Clock,
  AlertCircle,
  Ban,
  Activity,
  Calendar,
  Database,
  TrendingUp,
  HardDrive,
  ChevronRight,
  Copy,
  Plus,
  Edit
} from 'lucide-vue-next'
import { PipelineViewModel } from '@/types/generated'
import { format, formatDistance } from 'date-fns'
import { toast } from 'vue-sonner'

interface ExecutionLog {
  id: number
  level: string
  message: string
  context: any
  createdAt: string
}

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
  resultData: {
    totalRows?: number
    mappedRows?: number
    filteredRows?: number
    processingTime?: number | string
    stageTimings?: Record<string, number | string>
    memoryUsage?: Record<string, number | string> & { peak?: number | string }
    errorCount?: number
    stats?: {
      totalRows?: number
      mappedRows?: number
      filteredRows?: number
      processingTime?: number | string
      stageTimings?: Record<string, number | string>
      memoryUsage?: Record<string, number | string> & { peak?: number | string }
      errorCount?: number
    }
    saveResult?: {
      totalProcessed?: number
      createdCount?: number
      updatedCount?: number
      errorCount?: number
    }
    [key: string]: any
  } | null
  createdAt: string
  updatedAt: string
  logs: ExecutionLog[]
}

interface Props {
  pipeline: PipelineViewModel
  execution: Execution
}

const props = defineProps<Props>()

const getStatusBadge = () => {
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

  return statusConfig[props.execution.status] || statusConfig.pending
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

const formatMemoryMB = (bytes: number | string | null): string => {
  if (!bytes && bytes !== 0) return 'N/A'
  const numBytes = typeof bytes === 'string' ? parseFloat(bytes) : bytes
  if (isNaN(numBytes) || numBytes < 0) return 'N/A'
  const mb = numBytes / (1024 * 1024)
  return `${mb.toFixed(2)} MB`
}

const getSuccessRate = (rate: number | string | null): number | null => {
  if (rate === null || rate === undefined) return null
  const numRate = typeof rate === 'string' ? parseFloat(rate) : rate
  return isNaN(numRate) ? null : numRate
}

const getLogLevelColor = (level: string): string => {
  const levelColors: Record<string, string> = {
    error: 'text-red-600',
    warning: 'text-yellow-600',
    info: 'text-blue-600',
    debug: 'text-gray-600',
  }
  return levelColors[level.toLowerCase()] || 'text-gray-600'
}

const renderResultData = (data: any, key: string = '', depth: number = 0): any => {
  if (data === null || data === undefined) {
    return { type: 'null', value: 'null', key }
  }

  if (typeof data === 'string' || typeof data === 'number' || typeof data === 'boolean') {
    return { type: 'primitive', value: String(data), key }
  }

  if (Array.isArray(data)) {
    return { type: 'array', value: data, key, length: data.length }
  }

  if (typeof data === 'object') {
    return { type: 'object', value: data, key, keys: Object.keys(data) }
  }

  return { type: 'unknown', value: String(data), key }
}

const isExpandable = (data: any): boolean => {
  return (typeof data === 'object' && data !== null) || Array.isArray(data)
}

const copyToClipboard = async (text: string, key: string) => {
  try {
    await navigator.clipboard.writeText(text)
    toast.success('Copied!', {
      description: `${key} copied to clipboard`,
    })
  } catch (err) {
    toast.error('Failed to copy', {
      description: 'Could not copy to clipboard',
    })
  }
}
</script>

<template>
  <Head :title="`Execution #${execution.id} - ${pipeline.name}`" />
  
  <Default>
    <!-- Page Header -->
    <PageHeader 
      :title="`Execution #${execution.id}`"
      :description="`Detailed information about this pipeline execution`"
    >
      <template #actions>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.import.pipelines.executions', { pipeline: pipeline.id })">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Executions
          </Link>
        </Button>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.import.pipelines.show', { pipeline: pipeline.id })">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Pipeline
          </Link>
        </Button>
      </template>
    </PageHeader>


    <!-- Result Data Summary Cards -->
    <div v-if="execution.resultData?.stats && Object.keys(execution.resultData?.stats).length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6">
      <!-- Total Rows -->
      <Card v-if="execution.resultData?.stats.totalRows !== undefined">
        <CardContent class="pt-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Total Rows</p>
              <p class="text-2xl font-bold">{{ execution.resultData?.stats.totalRows?.toLocaleString() || 0 }}</p>
            </div>
            <Database class="h-8 w-8 text-muted-foreground" />
          </div>
        </CardContent>
      </Card>

      <!-- Mapped Rows -->
      <Card v-if="execution.resultData?.stats.mappedRows !== undefined">
        <CardContent class="pt-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Mapped Rows</p>
              <p class="text-2xl font-bold">{{ execution.resultData?.stats.mappedRows?.toLocaleString() || 0 }}</p>
            </div>
            <TrendingUp class="h-8 w-8 text-muted-foreground" />
          </div>
        </CardContent>
      </Card>

      <!-- Filtered Rows -->
      <Card v-if="execution.resultData?.stats.filteredRows !== undefined">
        <CardContent class="pt-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Filtered Rows</p>
              <p class="text-2xl font-bold">{{ execution.resultData?.stats.filteredRows?.toLocaleString() || 0 }}</p>
            </div>
            <Activity class="h-8 w-8 text-muted-foreground" />
          </div>
        </CardContent>
      </Card>

      <!-- Processing Time -->
      <Card v-if="execution.resultData?.stats.processingTime !== undefined">
        <CardContent class="pt-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Processing Time</p>
              <p class="text-2xl font-bold">{{ formatDuration(execution.resultData?.stats.processingTime) }}</p>
            </div>
            <Clock class="h-8 w-8 text-muted-foreground" />
          </div>
        </CardContent>
      </Card>

      <!-- Error Count -->
      <Card v-if="execution.resultData?.stats.errorCount !== undefined">
        <CardContent class="pt-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Error Count</p>
              <p class="text-2xl font-bold" :class="execution.resultData?.stats.errorCount > 0 ? 'text-destructive' : 'text-green-600'">
                {{ execution.resultData?.stats.errorCount?.toLocaleString() || 0 }}
              </p>
            </div>
            <AlertCircle class="h-8 w-8" :class="execution.resultData?.stats.errorCount > 0 ? 'text-destructive' : 'text-muted-foreground'" />
          </div>
        </CardContent>
      </Card>

      <!-- Peak Memory Usage -->
      <Card v-if="execution.resultData?.stats.memoryUsage?.peak !== undefined">
        <CardContent class="pt-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Peak Memory</p>
              <p class="text-2xl font-bold">{{ formatMemoryMB(execution.resultData?.stats.memoryUsage?.peak) }}</p>
            </div>
            <HardDrive class="h-8 w-8 text-muted-foreground" />
          </div>
        </CardContent>
      </Card>

      <!-- Created Count (Save Result) -->
      <Card v-if="execution.resultData?.saveResult?.createdCount !== undefined">
        <CardContent class="pt-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Created</p>
              <p class="text-2xl font-bold">{{ execution.resultData?.saveResult.createdCount?.toLocaleString() || 0 }}</p>
            </div>
            <Plus class="h-8 w-8 text-muted-foreground" />
          </div>
        </CardContent>
      </Card>

      <!-- Updated Count (Save Result) -->
      <Card v-if="execution.resultData?.saveResult?.updatedCount !== undefined">
        <CardContent class="pt-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Updated</p>
              <p class="text-2xl font-bold">{{ execution.resultData?.saveResult.updatedCount?.toLocaleString() || 0 }}</p>
            </div>
            <Edit class="h-8 w-8 text-muted-foreground" />
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Stage Timings and Memory Usage -->
    <div v-if="execution.resultData?.stats && (execution.resultData?.stats.stageTimings || execution.resultData?.stats.memoryUsage)" class="grid gap-6 md:grid-cols-2 mb-6">
      <!-- Stage Timings -->
      <Card v-if="execution.resultData?.stats.stageTimings">
        <CardHeader>
          <CardTitle>Stage Timings</CardTitle>
          <CardDescription>Time spent in each processing stage</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div v-for="(time, stage) in execution.resultData?.stats.stageTimings" :key="stage" class="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
              <div class="flex items-center gap-2">
                <Clock class="w-4 h-4 text-muted-foreground" />
                <span class="font-medium text-sm">{{ stage }}</span>
              </div>
              <span class="text-sm font-semibold">{{ formatDuration(time) }}</span>
            </div>
          </div>
        </CardContent>
      </Card>
      <!-- Memory Usage by Stage -->
      <Card v-if="execution.resultData?.stats.memoryUsage && Object.keys(execution.resultData?.stats.memoryUsage).filter(key => key !== 'peak').length > 0">
        <CardHeader>
          <CardTitle>Memory Usage by Stage</CardTitle>
          <CardDescription>Memory consumption per processing stage</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <template v-for="(memory, stage) in execution.resultData?.stats.memoryUsage" :key="stage">
              <div v-if="stage !== 'peak'" class="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                <div class="flex items-center gap-2">
                  <HardDrive class="w-4 h-4 text-muted-foreground" />
                  <span class="font-medium text-sm">{{ stage }}</span>
                </div>
                <span class="text-sm font-semibold">{{ formatMemoryMB(memory) }}</span>
              </div>
            </template>
          </div>
        </CardContent>
      </Card>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- Execution Information -->
      <Card>
        <CardHeader>
          <CardTitle>Execution Information</CardTitle>
          <CardDescription>Basic details about this execution</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Execution ID</div>
            <div class="text-lg font-semibold font-mono">#{{ execution.id }}</div>
          </div>

          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Status</div>
            <Badge 
              :variant="getStatusBadge().variant"
              :class="getStatusBadge().class"
              class="flex items-center gap-1.5 font-medium w-fit"
            >
              <component :is="getStatusBadge().icon" class="h-3 w-3" />
              {{ getStatusBadge().text }}
            </Badge>
          </div>

          <div v-if="execution.startedAt" class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Started At</div>
            <div class="flex items-center gap-2 text-sm">
              <Clock class="w-4 h-4 text-muted-foreground" />
              <div>
                <div>{{ format(new Date(execution.startedAt), 'PPpp') }}</div>
                <div class="text-xs text-muted-foreground">
                  {{ formatDistance(new Date(execution.startedAt), new Date(), { addSuffix: true }) }}
                </div>
              </div>
            </div>
          </div>

          <div v-if="execution.completedAt" class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Completed At</div>
            <div class="flex items-center gap-2 text-sm">
              <Clock class="w-4 h-4 text-muted-foreground" />
              <div>
                <div>{{ format(new Date(execution.completedAt), 'PPpp') }}</div>
                <div class="text-xs text-muted-foreground">
                  {{ formatDistance(new Date(execution.completedAt), new Date(), { addSuffix: true }) }}
                </div>
              </div>
            </div>
          </div>

          <div v-if="execution.startedAt && execution.completedAt" class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Duration</div>
            <div class="flex items-center gap-2 text-sm">
              <Activity class="w-4 h-4 text-muted-foreground" />
              {{ formatDuration(execution.processingTime) }}
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Performance Metrics -->
      <Card>
        <CardHeader>
          <CardTitle>Performance Metrics</CardTitle>
          <CardDescription>Execution performance and statistics</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div v-if="execution.totalRows > 0" class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Rows Processed</div>
            <div class="flex items-center gap-2">
              <Database class="w-4 h-4 text-muted-foreground" />
              <div>
                <div class="font-semibold">{{ execution.processedRows.toLocaleString() }} / {{ execution.totalRows.toLocaleString() }}</div>
                <div class="text-xs text-muted-foreground">
                  {{ execution.processedRows > 0 ? Math.round((execution.processedRows / execution.totalRows) * 100) : 0 }}% processed
                </div>
              </div>
            </div>
          </div>

          <div v-if="getSuccessRate(execution.successRate) !== null" class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Success Rate</div>
            <div class="flex items-center gap-2">
              <TrendingUp class="w-4 h-4 text-muted-foreground" />
              <div class="font-semibold" :class="(getSuccessRate(execution.successRate) || 0) >= 90 ? 'text-green-600' : (getSuccessRate(execution.successRate) || 0) >= 70 ? 'text-yellow-600' : 'text-red-600'">
                {{ (getSuccessRate(execution.successRate) || 0).toFixed(2) }}%
              </div>
            </div>
          </div>

          <div v-if="execution.processingTime" class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Processing Time</div>
            <div class="flex items-center gap-2 text-sm">
              <Clock class="w-4 h-4 text-muted-foreground" />
              {{ formatDuration(execution.processingTime) }}
            </div>
          </div>

          <div v-if="execution.memoryUsage" class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Memory Usage</div>
            <div class="flex items-center gap-2 text-sm">
              <HardDrive class="w-4 h-4 text-muted-foreground" />
              {{ formatMemory(execution.memoryUsage) }}
            </div>
          </div>

          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Created At</div>
            <div class="flex items-center gap-2 text-sm">
              <Calendar class="w-4 h-4 text-muted-foreground" />
              {{ format(new Date(execution.createdAt), 'PPpp') }}
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Error Message -->
    <Card v-if="execution.errorMessage">
      <CardHeader>
        <CardTitle class="text-destructive">Error Details</CardTitle>
        <CardDescription>Error information for this execution</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-950/20 rounded-lg border border-red-200 dark:border-red-900">
          <AlertCircle class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
          <div class="flex-1">
            <div class="font-medium text-red-900 dark:text-red-100">Error Message</div>
            <div class="mt-1 text-sm text-red-800 dark:text-red-200 whitespace-pre-wrap">{{ execution.errorMessage }}</div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Result Data -->
    <Card v-if="execution.resultData && Object.keys(execution.resultData).length > 0">
      <CardHeader>
        <CardTitle>Result Data</CardTitle>
        <CardDescription>Execution result information</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="space-y-2 overflow-x-hidden">
          <template v-for="(value, key) in execution.resultData" :key="key">
            <Collapsible v-if="isExpandable(value)" :default-open="false" class="border rounded-lg overflow-hidden w-full">
              <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 hover:bg-muted/50 transition-colors">
                <div class="flex items-center gap-2 min-w-0 flex-1">
                  <ChevronRight class="w-4 h-4 text-muted-foreground transition-transform duration-200 data-[state=open]:rotate-90 flex-shrink-0" />
                  <span class="font-medium text-sm truncate">{{ key }}</span>
                  <Badge v-if="Array.isArray(value)" variant="outline" class="text-xs flex-shrink-0">
                    Array ({{ value.length }})
                  </Badge>
                  <Badge v-else variant="outline" class="text-xs flex-shrink-0">
                    Object ({{ Object.keys(value).length }})
                  </Badge>
                </div>
                <Button
                  variant="ghost"
                  size="sm"
                  class="h-7 w-7 p-0 flex-shrink-0"
                  @click.stop="copyToClipboard(JSON.stringify(value, null, 2), key)"
                >
                  <Copy class="w-3.5 h-3.5" />
                  <span class="sr-only">Copy {{ key }}</span>
                </Button>
              </CollapsibleTrigger>
              <CollapsibleContent>
                <div class="px-4 pb-4 pt-2 border-t bg-muted/30 w-full overflow-hidden">
                  <div class="relative">
                    <pre class="bg-background p-3 rounded text-xs overflow-auto max-h-96 border whitespace-pre-wrap break-words word-break break-all">{{ JSON.stringify(value, null, 2) }}</pre>
                    <Button
                      variant="ghost"
                      size="sm"
                      class="absolute top-2 right-2 h-7 w-7 p-0 bg-background/80 hover:bg-background border"
                      @click="copyToClipboard(JSON.stringify(value, null, 2), key)"
                    >
                      <Copy class="w-3.5 h-3.5" />
                      <span class="sr-only">Copy {{ key }}</span>
                    </Button>
                  </div>
                </div>
              </CollapsibleContent>
            </Collapsible>
            <div v-else class="flex items-center gap-2 px-4 py-2 border rounded-lg">
              <span class="font-medium text-sm min-w-[120px]">{{ key }}:</span>
              <span class="text-sm text-muted-foreground break-all">{{ value }}</span>
            </div>
          </template>
        </div>
      </CardContent>
    </Card>
  </Default>
</template>

