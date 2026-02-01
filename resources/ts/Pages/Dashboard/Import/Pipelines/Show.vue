<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref, computed } from 'vue'
import Default from "@/components/Layoute/Default.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { 
  ArrowLeft,
  Edit, 
  Trash2, 
  Play, 
  Pause,
  Settings,
  Copy,
  Download,
  CheckCircle2,
  Circle,
  Calendar,
  Clock,
  Building2,
  User,
  Activity,
  History,
  ChevronDown
} from 'lucide-vue-next'
import { PipelineViewModel } from '@/types/generated'
import { format } from 'date-fns'


const props = defineProps<PipelineViewModel>()

const deleteDialogOpen = ref(false)
const configCardOpen = ref(false)

const company = computed(() => props.company || null)

const getStatusBadge = () => {
  if (props.isActive) {
    return { 
      variant: 'default' as const, 
      text: 'Active',
      icon: CheckCircle2,
      class: 'bg-green-500/10 text-green-700 dark:text-green-400 border-green-500/20'
    }
  }
  return { 
    variant: 'secondary' as const, 
    text: 'Inactive',
    icon: Circle,
    class: 'bg-muted text-muted-foreground'
  }
}

const deletePipeline = () => {
  deleteDialogOpen.value = true
}

const confirmDelete = () => {
  router.delete(route('dashboard.import.pipelines.destroy', { pipeline: props.id }), {
    onSuccess: () => {
      deleteDialogOpen.value = false
    },
    onError: () => {
      deleteDialogOpen.value = false
    },
  })
}

const cancelDelete = () => {
  deleteDialogOpen.value = false
}

const togglePipelineStatus = () => {
  router.patch(route('dashboard.import.pipelines.toggle-status', { pipeline: props.id }), {}, {
    preserveScroll: true,
  })
}

const processNow = () => {
  router.post(route('dashboard.import.pipelines.process-now', { pipeline: props.id }), {}, {
    preserveScroll: true,
  })
}
</script>

<template>
  <Head :title="`${name || 'Pipeline'} - Import Pipeline`" />
  
  <Default>
    <!-- Page Header -->
    <PageHeader 
      :title="name"
      :description="description || 'Pipeline details and configuration'"
    >
      <template #actions>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.import.pipelines.index')">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Pipelines
          </Link>
        </Button>
        <Button variant="outline" size="sm" @click="togglePipelineStatus">
          <Play v-if="isActive" class="w-4 h-4 mr-2" />
          <Pause v-else class="w-4 h-4 mr-2" />
          {{ isActive ? 'Pause' : 'Start' }} Pipeline
        </Button>
        <Button variant="outline" size="sm" @click="processNow" :disabled="!isActive">
          <Activity class="w-4 h-4 mr-2" />
          Process Now
        </Button>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.import.pipelines.executions', { pipeline: id })">
            <History class="w-4 h-4 mr-2" />
            Execution History
          </Link>
        </Button>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.import.pipelines.activity-logs', { pipeline: id })">
            <Activity class="w-4 h-4 mr-2" />
            Change Logs
          </Link>
        </Button>
        <Button variant="outline" size="sm" as-child>
          <Link :href="route('dashboard.import.pipelines.stepper.edit', { pipeline: id })">
            <Edit class="w-4 h-4 mr-2" />
            Edit Pipeline
          </Link>
        </Button>
        <Button variant="destructive" size="sm" @click="deletePipeline">
          <Trash2 class="w-4 h-4 mr-2" />
          Delete
        </Button>
      </template>
    </PageHeader>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- Pipeline Information -->
      <Card>
        <CardHeader>
          <CardTitle>Pipeline Information</CardTitle>
          <CardDescription>Basic details about this import pipeline</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Name</div>
            <div class="text-lg font-semibold">{{ name }}</div>
          </div>
          
          <div v-if="description" class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Description</div>
            <div class="text-sm">{{ description }}</div>
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

          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Frequency</div>
            <Badge variant="outline" class="font-medium">{{frequency }}</Badge>
          </div>

          <div v-if="startTime" class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Start Time</div>
            <div class="flex items-center gap-2 text-sm">
              <Clock class="w-4 h-4 text-muted-foreground" />
              {{formattedStartTime || startTime }}
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Company & Metadata -->
      <Card>
        <CardHeader>
          <CardTitle>Company & Metadata</CardTitle>
          <CardDescription>Company association and creation details</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Company</div>
            <div v-if="company" class="flex items-center gap-2">
              <Building2 class="w-4 h-4 text-muted-foreground" />
              <div>
                <div class="font-medium">{{ company.name }}</div>
                <div v-if="company.email" class="text-sm text-muted-foreground">
                  {{ company.email }}
                </div>
              </div>
            </div>
            <div v-else class="flex items-center gap-2">
              <Building2 class="w-4 h-4 text-muted-foreground" />
              <span class="text-muted-foreground">No company assigned</span>
            </div>
          </div>

          <div v-if="createdBy" class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Created By</div>
            <div class="flex items-center gap-2">
              <User class="w-4 h-4 text-muted-foreground" />
              <span>{{createdBy }}</span>
            </div>
          </div>

          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Created At</div>
            <div class="flex items-center gap-2 text-sm">
              <Calendar class="w-4 h-4 text-muted-foreground" />
              {{ format(new Date(createdAt), 'PPpp') }}
            </div>
          </div>

          <div class="space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Last Updated</div>
            <div class="flex items-center gap-2 text-sm">
              <Calendar class="w-4 h-4 text-muted-foreground" />
              {{ format(new Date(updatedAt), 'PPpp') }}
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Configuration -->
    <Collapsible v-if="config" v-model:open="configCardOpen" class="w-full">
      <Card>
        <CollapsibleTrigger as-child>
          <CardHeader class="cursor-pointer hover:bg-muted/50 transition-colors">
            <div class="flex items-center justify-between">
              <div>
                <CardTitle>Configuration</CardTitle>
                <CardDescription>Pipeline configuration details</CardDescription>
              </div>
              <ChevronDown 
                class="h-5 w-5 text-muted-foreground transition-transform duration-200"
                :class="{ 'rotate-180': configCardOpen }"
              />
            </div>
          </CardHeader>
        </CollapsibleTrigger>
        <CollapsibleContent>
          <CardContent>
            <pre class="bg-muted p-4 rounded-lg text-sm overflow-auto">{{ JSON.stringify(config, null, 2) }}</pre>
          </CardContent>
        </CollapsibleContent>
      </Card>
    </Collapsible>

    <!-- Delete Confirmation Dialog -->
    <AlertDialog v-model:open="deleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete Pipeline</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to delete the pipeline 
            <span class="font-semibold text-foreground">"{{name }}"</span>? 
            This action cannot be undone and will permanently remove the pipeline and all its associated data.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel @click="cancelDelete">Cancel</AlertDialogCancel>
          <AlertDialogAction
            @click="confirmDelete"
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          >
            Delete Pipeline
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </Default>
</template>

