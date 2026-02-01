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
  Activity,
  Calendar,
  User,
  FileText,
  Settings,
  ChevronDown,
  Copy,
  Info
} from 'lucide-vue-next'
import { PipelineViewModel, ActivityLogViewModel } from '@/types/generated'
import { format, formatDistance } from 'date-fns'
import { toast } from 'vue-sonner'
import { computed } from 'vue'

interface Props {
  pipeline: PipelineViewModel
  activity: ActivityLogViewModel
}

const props = defineProps<Props>()

const getEventBadge = () => {
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

  const event = props.activity.event || 'updated'
  return eventConfig[event] || {
    variant: 'secondary' as const,
    text: event.charAt(0).toUpperCase() + event.slice(1),
    class: 'bg-gray-500/10 text-gray-700 dark:text-gray-400 border-gray-500/20'
  }
}

const getSubjectTypeLabel = (): string => {
  if (props.activity.subjectType === 'App\\Models\\Import\\ImportPipeline') {
    return 'Pipeline'
  }
  if (props.activity.subjectType === 'App\\Models\\Import\\ImportPipelineConfig') {
    return 'Configuration'
  }
  return props.activity.subjectType?.split('\\').pop() || 'Unknown'
}

const hasChanges = computed(() => {
  const changes = props.activity.changes
  return (changes.attributes && Object.keys(changes.attributes).length > 0) ||
         (changes.old && Object.keys(changes.old).length > 0)
})

const copyToClipboard = async (text: string, label: string) => {
  try {
    await navigator.clipboard.writeText(text)
    toast.success('Copied!', {
      description: `${label} copied to clipboard`,
    })
  } catch (err) {
    toast.error('Failed to copy', {
      description: 'Could not copy to clipboard',
    })
  }
}

const formatPropertyValue = (value: any): string => {
  if (value === null) return 'null'
  if (value === undefined) return 'undefined'
  if (typeof value === 'object') {
    return JSON.stringify(value, null, 2)
  }
  return String(value)
}
</script>

<template>
  <Head :title="`Change Log #${activity.id} - ${pipeline.name}`" />
  
  <Default>
    <!-- Page Header -->
    <PageHeader 
      :title="`Change Log #${activity.id}`"
      :description="`Detailed information about this pipeline change`"
    >
      <template #actions>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.import.pipelines.activity-logs', { pipeline: pipeline.id })">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Change Logs
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

    <div class="grid gap-6 md:grid-cols-2">
      <!-- Activity Information -->
      <Card>
        <CardHeader>
          <CardTitle>Activity Information</CardTitle>
          <CardDescription>Basic details about this change</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Activity ID</div>
            <div class="text-lg font-semibold font-mono">#{{ activity.id }}</div>
          </div>

          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Event</div>
            <Badge 
              :variant="getEventBadge().variant"
              :class="getEventBadge().class"
              class="flex items-center gap-1.5 font-medium w-fit"
            >
              {{ getEventBadge().text }}
            </Badge>
          </div>

          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Description</div>
            <div class="text-sm">{{ activity.description }}</div>
          </div>

          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Subject</div>
            <div class="flex items-center gap-2">
              <component 
                :is="activity.subjectType === 'App\\Models\\Import\\ImportPipelineConfig' ? Settings : FileText"
                class="w-4 h-4 text-muted-foreground"
              />
              <span class="text-sm">{{ getSubjectTypeLabel() }}</span>
              <span v-if="activity.subjectId" class="text-sm text-muted-foreground font-mono">
                (ID: {{ activity.subjectId }})
              </span>
            </div>
          </div>

          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Changed By</div>
            <div class="flex items-center gap-2">
              <User class="w-4 h-4 text-muted-foreground" />
              <span class="text-sm">{{ activity.causerName || 'System' }}</span>
            </div>
          </div>

          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Date</div>
            <div class="flex items-center gap-2 text-sm">
              <Calendar class="w-4 h-4 text-muted-foreground" />
              <div>
                <div>{{ format(new Date(activity.createdAt), 'PPpp') }}</div>
                <div class="text-xs text-muted-foreground">
                  {{ formatDistance(new Date(activity.createdAt), new Date(), { addSuffix: true }) }}
                </div>
              </div>
            </div>
          </div>

          <div v-if="activity.logName" class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Log Name</div>
            <div class="text-sm font-mono">{{ activity.logName }}</div>
          </div>
        </CardContent>
      </Card>

      <!-- Change Details -->
      <Card>
        <CardHeader>
          <CardTitle>Change Details</CardTitle>
          <CardDescription>What was changed in this activity</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div v-if="hasChanges" class="space-y-4">
            <!-- New Values -->
            <div v-if="activity.changes.attributes && Object.keys(activity.changes.attributes).length > 0">
              <div class="text-sm font-medium text-muted-foreground mb-2">New Values</div>
              <div class="space-y-2">
                <div 
                  v-for="(value, key) in activity.changes.attributes" 
                  :key="key"
                  class="p-2 bg-green-50 dark:bg-green-950/20 rounded border border-green-200 dark:border-green-900"
                >
                  <div class="text-xs font-medium text-green-900 dark:text-green-100 mb-1">{{ key }}</div>
                  <div class="text-sm text-green-800 dark:text-green-200 font-mono break-all">
                    {{ formatPropertyValue(value) }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Old Values -->
            <div v-if="activity.changes.old && Object.keys(activity.changes.old).length > 0">
              <div class="text-sm font-medium text-muted-foreground mb-2">Old Values</div>
              <div class="space-y-2">
                <div 
                  v-for="(value, key) in activity.changes.old" 
                  :key="key"
                  class="p-2 bg-red-50 dark:bg-red-950/20 rounded border border-red-200 dark:border-red-900"
                >
                  <div class="text-xs font-medium text-red-900 dark:text-red-100 mb-1">{{ key }}</div>
                  <div class="text-sm text-red-800 dark:text-red-200 font-mono break-all">
                    {{ formatPropertyValue(value) }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-sm text-muted-foreground">
            No specific changes recorded for this activity.
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Properties -->
    <Card v-if="activity.properties && Object.keys(activity.properties).length > 0">
      <CardHeader>
        <div class="flex items-center justify-between">
          <div>
            <CardTitle>Properties</CardTitle>
            <CardDescription>All properties associated with this activity</CardDescription>
          </div>
          <Button
            variant="outline"
            size="sm"
            @click="copyToClipboard(JSON.stringify(activity.properties, null, 2), 'Properties')"
          >
            <Copy class="w-4 h-4 mr-2" />
            Copy All
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        <div class="space-y-2">
          <template v-for="(value, key) in activity.properties" :key="key">
            <Collapsible 
              v-if="typeof value === 'object' && value !== null" 
              :default-open="false" 
              class="border rounded-lg overflow-hidden"
            >
              <CollapsibleTrigger class="flex w-full items-center justify-between px-4 py-3 hover:bg-muted/50 transition-colors">
                <div class="flex items-center gap-2 min-w-0 flex-1">
                  <ChevronDown class="w-4 h-4 text-muted-foreground transition-transform duration-200 data-[state=open]:rotate-180 flex-shrink-0" />
                  <span class="font-medium text-sm truncate">{{ key }}</span>
                  <Badge 
                    v-if="Array.isArray(value)" 
                    variant="outline" 
                    class="text-xs flex-shrink-0"
                  >
                    Array ({{ value.length }})
                  </Badge>
                  <Badge 
                    v-else 
                    variant="outline" 
                    class="text-xs flex-shrink-0"
                  >
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
                <div class="px-4 pb-4 pt-2 border-t bg-muted/30">
                  <div class="relative">
                    <pre class="bg-background p-3 rounded text-xs overflow-auto max-h-96 border whitespace-pre-wrap break-words">{{ JSON.stringify(value, null, 2) }}</pre>
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
              <span class="text-sm text-muted-foreground break-all font-mono">{{ formatPropertyValue(value) }}</span>
              <Button
                variant="ghost"
                size="sm"
                class="h-7 w-7 p-0 ml-auto"
                @click="copyToClipboard(formatPropertyValue(value), key)"
              >
                <Copy class="w-3.5 h-3.5" />
                <span class="sr-only">Copy {{ key }}</span>
              </Button>
            </div>
          </template>
        </div>
      </CardContent>
    </Card>

    <!-- Raw Data -->
    <Card>
      <CardHeader>
        <div class="flex items-center justify-between">
          <div>
            <CardTitle>Raw Activity Data</CardTitle>
            <CardDescription>Complete activity log entry in JSON format</CardDescription>
          </div>
          <Button
            variant="outline"
            size="sm"
            @click="copyToClipboard(JSON.stringify(activity, null, 2), 'Activity Data')"
          >
            <Copy class="w-4 h-4 mr-2" />
            Copy JSON
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        <div class="relative">
          <pre class="bg-muted p-4 rounded-lg text-sm overflow-auto max-h-96 border whitespace-pre-wrap break-words">{{ JSON.stringify(activity, null, 2) }}</pre>
          <Button
            variant="ghost"
            size="sm"
            class="absolute top-2 right-2 h-7 w-7 p-0 bg-background/80 hover:bg-background border"
            @click="copyToClipboard(JSON.stringify(activity, null, 2), 'Activity Data')"
          >
            <Copy class="w-3.5 h-3.5" />
            <span class="sr-only">Copy JSON</span>
          </Button>
        </div>
      </CardContent>
    </Card>
  </Default>
</template>

