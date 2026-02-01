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
  ArrowLeft,
  Activity,
  RefreshCw,
  Eye,
  User,
  FileText,
  Settings
} from 'lucide-vue-next'
import { PipelineViewModel, ActivityLogViewModel } from '@/types/generated'
import { format, formatDistance } from 'date-fns'

interface Props {
  pipeline: PipelineViewModel
  logs: ActivityLogViewModel[]
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

const getEventBadge = (log: ActivityLogViewModel) => {
  const eventConfig: Record<string, { variant: 'default' | 'secondary' | 'destructive' | 'outline', text: string, class: string }> = {
    created: {
      variant: 'default',
      text: 'Created',
      class: 'bg-green-500/10 text-green-700 dark:text-green-400 border-green-500/20'
    },
    updated: {
      variant: 'default',
      text: 'Updated',
      class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20'
    },
    deleted: {
      variant: 'destructive',
      text: 'Deleted',
      class: 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-500/20'
    }
  }

  const event = log.event || 'updated'
  return eventConfig[event] || {
    variant: 'secondary' as const,
    text: event.charAt(0).toUpperCase() + event.slice(1),
    class: 'bg-gray-500/10 text-gray-700 dark:text-gray-400 border-gray-500/20'
  }
}

const getSubjectTypeLabel = (log: ActivityLogViewModel): string => {
  if (log.subjectType === 'App\\Models\\Import\\ImportPipeline') {
    return 'Pipeline'
  }
  if (log.subjectType === 'App\\Models\\Import\\ImportPipelineConfig') {
    return 'Config'
  }
  return log.subjectType?.split('\\').pop() || 'Unknown'
}

const refresh = () => {
  const currentRoute = route().current()
  const currentParams = route().params
  
  if (currentRoute) {
    router.visit(route(currentRoute, currentParams), {
      only: ['logs', 'paginator'],
      preserveScroll: true,
    })
  } else {
    router.reload({
      only: ['logs', 'paginator'],
    })
  }
}
</script>

<template>
  <Head :title="`${props.pipeline.name} - Change Logs`" />
  
  <Default>
    <!-- Page Header -->
    <PageHeader 
      :title="`Change Logs: ${props.pipeline.name}`"
      :description="`View all changes and modifications made to this pipeline and its configuration`"
    >
      <template #actions>
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

    <!-- Activity Logs Table -->
    <Card class="shadow-sm w-full">
      <CardHeader class="pb-4 border-b">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="space-y-1">
            <CardTitle class="text-xl font-bold">Change Logs</CardTitle>
            <CardDescription class="text-sm">
              A chronological list of all changes made to this pipeline and its configuration
            </CardDescription>
          </div>
          <div class="text-sm text-muted-foreground">
            Total: {{ paginator.total }} {{ paginator.total === 1 ? 'change' : 'changes' }}
          </div>
        </div>
      </CardHeader>
      <CardContent class="pt-6">
        <div class="overflow-x-auto rounded-lg border bg-background" style="max-width: 100%;">
          <Table class="min-w-full">
            <TableHeader>
              <TableRow>
                <TableHead class="w-[80px]">ID</TableHead>
                <TableHead class="w-[120px]">Event</TableHead>
                <TableHead class="w-[150px]">Subject</TableHead>
                <TableHead>Description</TableHead>
                <TableHead class="w-[180px]">Changed By</TableHead>
                <TableHead class="w-[220px]">Date</TableHead>
                <TableHead class="w-[100px] text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableEmpty v-if="logs.length === 0" :colspan="7">
                <div class="text-center py-16 px-4">
                  <div class="mx-auto w-20 h-20 rounded-full bg-muted flex items-center justify-center mb-6">
                    <Activity class="h-10 w-10 text-muted-foreground" />
                  </div>
                  <h3 class="mt-2 text-xl font-bold">No changes yet</h3>
                  <p class="mt-2 text-sm text-muted-foreground max-w-md mx-auto">
                    This pipeline hasn't been modified yet. Changes will appear here once you update the pipeline or its configuration.
                  </p>
                </div>
              </TableEmpty>
              <TableRow 
                v-for="log in logs" 
                :key="log.id"
                class="hover:bg-muted/50 transition-colors cursor-pointer"
                @click="router.visit(route('dashboard.import.pipelines.activity-logs.show', { pipeline: props.pipeline.id, activity: log.id }))"
              >
                <TableCell class="text-sm text-muted-foreground font-mono whitespace-nowrap w-[80px]">
                  #{{ log.id }}
                </TableCell>
                <TableCell class="whitespace-nowrap w-[120px]">
                  <Badge 
                    :variant="getEventBadge(log).variant"
                    :class="getEventBadge(log).class"
                    class="flex items-center gap-1.5 font-medium w-fit"
                  >
                    {{ getEventBadge(log).text }}
                  </Badge>
                </TableCell>
                <TableCell class="text-sm whitespace-nowrap w-[150px]">
                  <div class="flex items-center gap-2">
                    <component 
                      :is="log.subjectType === 'App\\Models\\Import\\ImportPipelineConfig' ? Settings : FileText"
                      class="w-4 h-4 text-muted-foreground"
                    />
                    <span>{{ getSubjectTypeLabel(log) }}</span>
                  </div>
                </TableCell>
                <TableCell class="text-sm">
                  <div class="max-w-md truncate" :title="log.description">
                    {{ log.description }}
                  </div>
                </TableCell>
                <TableCell class="text-sm whitespace-nowrap w-[180px]">
                  <div v-if="log.causerName" class="flex items-center gap-2">
                    <User class="w-4 h-4 text-muted-foreground" />
                    <span>{{ log.causerName }}</span>
                  </div>
                  <span v-else class="text-muted-foreground">System</span>
                </TableCell>
                <TableCell class="text-sm w-[220px]">
                  <div class="space-y-0.5">
                    <div class="whitespace-nowrap">{{ format(new Date(log.createdAt), 'MMM dd, yyyy HH:mm:ss') }}</div>
                    <div class="text-xs text-muted-foreground whitespace-nowrap">
                      {{ formatDistance(new Date(log.createdAt), new Date(), { addSuffix: true }) }}
                    </div>
                  </div>
                </TableCell>
                <TableCell class="w-[100px] text-right" @click.stop>
                  <Button variant="ghost" size="sm" as-child>
                    <Link :href="route('dashboard.import.pipelines.activity-logs.show', { pipeline: props.pipeline.id, activity: log.id })">
                      <Eye class="w-4 h-4" />
                      <span class="sr-only">View details</span>
                    </Link>
                  </Button>
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
              {{ paginator.total === 1 ? 'change' : 'changes' }}
            </div>
            <Pagination :paginator="paginator" />
          </div>
        </div>
      </CardContent>
    </Card>
  </Default>
</template>

