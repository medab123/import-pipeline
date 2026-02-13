
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { ref, watch, computed } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import Default from "@/components/Layoute/Default.vue"
import ListingStatistics from "./Partials/ListingStatistics.vue"
import { PageHeader } from '@/components/ui/page-header'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow, TableEmpty } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Pagination } from '@/components/ui/pagination'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'
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
  Plus,
  Play,
  Pause,
  Edit,
  Trash2,
  Eye,
  Activity,
  MoreHorizontal,
  Search,
  History,
  X,
  CheckCircle2,
  Circle,
  RefreshCw,
  Download
} from 'lucide-vue-next'
import { ListPipelineViewModel, PipelineViewModel } from '@/types/generated'
import { format } from 'date-fns'
import * as Icons from 'lucide-vue-next'
import { usePermissions } from '@/composables/usePermissions'

const props = defineProps<ListPipelineViewModel>()

// Permission helpers (similar to Laravel's can/cant)
const { can } = usePermissions()

// Initialize search query from URL query params if present
const getSearchFromUrl = () => {
  const urlParams = new URLSearchParams(window.location.search)
  return urlParams.get('search') || ''
}

const searchQuery = ref(getSearchFromUrl())
const deleteDialogOpen = ref(false)
const pipelineToDelete = ref<PipelineViewModel | null>(null)

// Debounced search function to make backend requests
const performSearch = useDebounceFn(() => {
  const searchValue = searchQuery.value.trim()
  const queryParams: Record<string, string | number | undefined> = {
    page: 1, // Reset to first page when searching
  }

  if (searchValue) {
    queryParams.search = searchValue
  }

  router.get(
    route('dashboard.import.pipelines.index'),
    queryParams,
    {
      preserveState: true,
      preserveScroll: false,
      only: ['pipelines', 'paginator'],
      replace: true,
    }
  )
}, 500)

let isInitialLoad = true

watch(searchQuery, () => {
  if (isInitialLoad) {
    isInitialLoad = false
    return
  }
  performSearch()
})

const clearSearch = () => {
  searchQuery.value = ''
  // Clear search immediately and reset to page 1
  router.get(
    route('dashboard.import.pipelines.index'),
    { page: 1 },
    {
      preserveState: true,
      preserveScroll: false,
      only: ['pipelines', 'paginator'],
      replace: true,
    }
  )
}


const togglePipelineStatus = (pipeline: PipelineViewModel) => {
  router.patch(route('dashboard.import.pipelines.toggle-status', { pipeline: pipeline.id }), {}, {
    preserveScroll: true,
  })
}

const processNow = (pipeline: PipelineViewModel) => {
  router.post(route('dashboard.import.pipelines.process-now', { pipeline: pipeline.id }), {}, {
    preserveScroll: true,
  })
}

const deletePipeline = (pipeline: PipelineViewModel) => {
  pipelineToDelete.value = pipeline
  deleteDialogOpen.value = true
}

const confirmDelete = () => {
  if (!pipelineToDelete.value) {
    return
  }

  router.delete(route('dashboard.import.pipelines.destroy', { pipeline: pipelineToDelete.value.id }), {
    onSuccess: () => {
      deleteDialogOpen.value = false
      pipelineToDelete.value = null
    },
    onError: () => {
      // Error handling is done by the backend toast notification
      deleteDialogOpen.value = false
      pipelineToDelete.value = null
    },
  })
}

const cancelDelete = () => {
  deleteDialogOpen.value = false
  pipelineToDelete.value = null
}


const getIcon = (iconName:any) => {
  return Icons[iconName] || null
}

const refresh = () => {
  const currentRoute = route().current()
  const currentParams = route().params
  const queryParams: Record<string, string | undefined> = {}

  if (searchQuery.value.trim()) {
    queryParams.search = searchQuery.value.trim()
  }

  if (currentRoute) {
    router.visit(route(currentRoute, currentParams), {
      data: queryParams,
      only: ['pipelines', 'paginator', 'stats'],
      preserveScroll: true,
    })
  } else {
    router.reload({
      only: ['pipelines', 'paginator', 'stats'],
    })
  }
}

const exportPipeline = (pipeline: PipelineViewModel) => {
  window.location.href = route('dashboard.import.pipelines.export', { pipeline: pipeline.id })
}

const importFileInput = ref<HTMLInputElement | null>(null)

const triggerImport = () => {
  importFileInput.value?.click()
}

const handleImportChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (!target.files || target.files.length === 0) {
    return
  }

  const file = target.files[0]
  const formData = new FormData()
  formData.append('yaml_file', file)

  router.post(route('dashboard.import.pipelines.import'), formData, {
    preserveScroll: true,
    onFinish: () => {
      // Reset the file input so the same file can be chosen again if needed
      if (importFileInput.value) {
        importFileInput.value.value = ''
      }
    },
  })
}

</script>

<template>
  <Head title="Import Pipelines" />

  <Default>
    <!-- Page Header -->
    <PageHeader
      title="Import Pipelines"
      description="Manage and monitor your data import pipelines"
    >
      <template #actions>
        <input
          v-if="can('import pipelines')"
          ref="importFileInput"
          type="file"
          accept=".yml,.yaml"
          class="hidden"
          @change="handleImportChange"
        />
        <Button
          v-if="can('import pipelines')"
          variant="outline"
          size="sm"
          @click="triggerImport"
          class="mr-2"
        >
          <Download class="w-4 h-4 mr-2" />
          Import YAML
        </Button>
        <Button variant="outline" size="sm" @click="refresh">
          <RefreshCw class="w-4 h-4 mr-2" />
          Refresh
        </Button>
        <Button as-child>
          <Link :href="route('dashboard.import.pipelines.stepper.create')">
            <Plus class="w-4 h-4 mr-2" />
            Create Pipeline
          </Link>
        </Button>
      </template>
    </PageHeader>

    <!-- Statistics Cards -->
    <ListingStatistics
      v-if="props.stats"
      :stats="props.stats"
    />

    <!-- Pipelines Table -->
    <Card class="shadow-sm">
      <CardHeader class="pb-4 border-b">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="space-y-1">
            <CardTitle class="text-xl font-bold">Pipelines</CardTitle>
            <CardDescription class="text-sm">
              A list of all your import pipelines and their current status
            </CardDescription>
          </div>
          <div class="relative w-full sm:w-auto sm:max-w-sm">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
            <Input
              v-model="searchQuery"
              placeholder="Search pipelines..."
              class="pl-9 pr-9 w-full"
            />
            <button
              v-if="searchQuery"
              @click="clearSearch"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
              type="button"
              aria-label="Clear search"
            >
              <X class="h-4 w-4" />
            </button>
          </div>
        </div>
      </CardHeader>
      <CardContent class="pt-6">
        <div class="overflow-x-auto rounded-lg border bg-background max-w-full">
          <Table class="min-w-full">
            <TableHeader>
              <TableRow>
                <TableHead class="w-[80px]">ID</TableHead>
                <TableHead class="w-[250px]">Name</TableHead>
                <TableHead class="w-[180px]">Company</TableHead>
                <TableHead class="w-[120px]">Frequency</TableHead>
                <TableHead class="w-[180px]">Last Executed</TableHead>
                <TableHead class="w-[180px]">Next Execution</TableHead>
                <TableHead class="w-[120px]">Status</TableHead>
                <TableHead class="w-[140px]">Created By</TableHead>
                <TableHead class="w-[140px]">Updated By</TableHead>
                <TableHead class="w-[180px]">Created At</TableHead>
                <TableHead class="w-[100px] text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableEmpty v-if="props.pipelines.length === 0 && !searchQuery" :colspan="10">
                <div class="text-center py-16 px-4">
                  <div class="mx-auto w-20 h-20 rounded-full bg-gradient-to-br from-primary/10 to-primary/5 flex items-center justify-center mb-6 ring-4 ring-primary/5">
                    <Activity class="h-10 w-10 text-primary" />
                  </div>
                  <h3 class="mt-2 text-xl font-bold">No pipelines yet</h3>
                  <p class="mt-2 text-sm text-muted-foreground max-w-md mx-auto leading-relaxed">
                    Get started by creating your first import pipeline to automate data imports.
                  </p>
                  <div class="mt-8">
                    <Button as-child size="lg" class="shadow-md">
                      <Link :href="route('dashboard.import.pipelines.stepper.create')">
                        <Plus class="w-4 h-4 mr-2" />
                        Create Your First Pipeline
                      </Link>
                    </Button>
                  </div>
                </div>
              </TableEmpty>
              <TableEmpty v-else-if="props.pipelines.length === 0 && searchQuery" :colspan="10">
                <div class="text-center py-16 px-4">
                  <div class="mx-auto w-20 h-20 rounded-full bg-muted flex items-center justify-center mb-6">
                    <Search class="h-10 w-10 text-muted-foreground" />
                  </div>
                  <h3 class="mt-2 text-xl font-bold">No results found</h3>
                  <p class="mt-2 text-sm text-muted-foreground max-w-md mx-auto">
                    No pipelines match your search "<span class="font-medium text-foreground">{{ searchQuery }}</span>"
                  </p>
                  <div class="mt-6">
                    <Button variant="outline" @click="clearSearch">
                      <X class="w-4 h-4 mr-2" />
                      Clear Search
                    </Button>
                  </div>
                </div>
              </TableEmpty>
              <TableRow
                v-for="pipeline in props.pipelines"
                :key="pipeline.id"
                class="group hover:bg-muted/50 transition-colors cursor-pointer"
                @click="router.visit(route('dashboard.import.pipelines.show', { pipeline: pipeline.id }))"
              >
                <TableCell @click.stop class="text-sm text-muted-foreground font-mono whitespace-nowrap w-[80px]">
                  #{{ pipeline.id }}
                </TableCell>
                <TableCell class="font-medium w-[250px]" @click.stop>
                  <div class="space-y-1.5">
                    <Link
                      :href="route('dashboard.import.pipelines.show', { pipeline: pipeline.id })"
                      class="font-semibold group-hover:text-primary transition-colors flex items-center gap-2 whitespace-nowrap hover:text-primary hover:underline"
                    >
                      <span>{{ pipeline.name }}</span>
                    </Link>
                  </div>
                </TableCell>
                <TableCell @click.stop class="whitespace-nowrap w-[180px]">
                  <div v-if="pipeline.company" class="space-y-0.5">
                    <div class="font-medium text-sm">{{ pipeline.company.name }}</div>
                    <div class="text-xs text-muted-foreground">{{ pipeline.company.email }}</div>
                  </div>
                  <span v-else class="text-muted-foreground text-sm">N/A</span>
                </TableCell>
                <TableCell @click.stop class="whitespace-nowrap w-[120px]">
                  <Badge variant="outline" class="font-medium">{{ pipeline.frequency }}</Badge>
                </TableCell>
                <TableCell @click.stop class="text-sm text-muted-foreground whitespace-nowrap w-[180px]">
                  <div v-if="pipeline.lastExecutedAt" class="space-y-0.5">
                    <div class="font-medium text-foreground">
                      {{ format(new Date(pipeline.lastExecutedAt), 'MMM dd, yyyy') }}
                    </div>
                    <div class="text-xs">
                      {{ format(new Date(pipeline.lastExecutedAt), 'HH:mm') }}
                    </div>
                  </div>
                  <span v-else class="text-muted-foreground text-sm italic">Never</span>
                </TableCell>
                <TableCell @click.stop class="text-sm whitespace-nowrap w-[180px]">
                  <div v-if="pipeline.nextExecutionAt" class="space-y-0.5">
                    <div class="font-medium text-foreground">
                      {{ format(new Date(pipeline.nextExecutionAt), 'MMM dd, yyyy') }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                      {{ format(new Date(pipeline.nextExecutionAt), 'HH:mm') }}
                    </div>
                  </div>
                  <span v-else class="text-muted-foreground text-sm italic">Not scheduled</span>
                </TableCell>
                <TableCell @click.stop class="whitespace-nowrap w-[120px]">
                  <Badge
                    :variant="pipeline.status.variant"
                    :class="pipeline.status.class"
                    class="flex items-center gap-1.5 font-medium w-fit"
                  >
                    <component :is="getIcon(pipeline.status.icon)" class="h-4 w-4" />
                    {{ pipeline.status.text }}
                  </Badge>
                </TableCell>
                <TableCell @click.stop class="text-sm whitespace-nowrap w-[140px]">
                  <span class="text-muted-foreground">{{ pipeline.createdBy || 'N/A' }}</span>
                </TableCell>
                <TableCell @click.stop class="text-sm whitespace-nowrap w-[140px]">
                  <span class="text-muted-foreground">{{ pipeline.updatedBy || 'N/A' }}</span>
                </TableCell>
                <TableCell @click.stop class="text-sm text-muted-foreground whitespace-nowrap w-[180px]">
                  {{ format(new Date(pipeline.createdAt), 'MMM dd, yyyy') }}
                  <span class="text-xs ml-1">{{ format(new Date(pipeline.createdAt), 'HH:mm') }}</span>
                </TableCell>
                <TableCell class="w-[100px] text-right" @click.stop>
                  <div class="flex items-center justify-end gap-2">
                    <Button
                      variant="ghost"
                      size="icon"
                      class="h-8 w-8 text-muted-foreground hover:text-foreground"
                      as-child
                    >
                      <Link :href="route('dashboard.import.pipelines.show', { pipeline: pipeline.id })">
                        <Eye class="h-4 w-4" />
                        <span class="sr-only">View Details</span>
                      </Link>
                    </Button>
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <Button
                          variant="ghost"
                          size="icon"
                          class="h-8 w-8 text-muted-foreground hover:text-foreground"
                        >
                          <MoreHorizontal class="h-4 w-4" />
                          <span class="sr-only">Open menu</span>
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end" class="w-56">
                        <DropdownMenuItem as-child>
                          <Link :href="route('dashboard.import.pipelines.show', { pipeline: pipeline.id })" class="flex items-center">
                            <Eye class="w-4 h-4 mr-2" />
                            View Details
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                          <Link :href="route('dashboard.import.pipelines.executions', { pipeline: pipeline.id })" class="flex items-center">
                            <History class="w-4 h-4 mr-2" />
                            Execution History
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                          v-if="can('export pipelines')"
                          @click="() => exportPipeline(pipeline)"
                          class="flex items-center"
                        >
                          <Download class="w-4 h-4 mr-2" />
                          Export as YAML
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                          <Link :href="route('dashboard.import.pipelines.stepper.edit', { pipeline: pipeline.id })" class="flex items-center">
                            <Edit class="w-4 h-4 mr-2" />
                            Edit Pipeline
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem @click="() => togglePipelineStatus(pipeline)" class="flex items-center">
                          <Play v-if="!pipeline.isActive" class="w-4 h-4 mr-2" />
                          <Pause v-else class="w-4 h-4 mr-2" />
                          {{ pipeline.isActive ? 'Pause' : 'Start' }} Pipeline
                        </DropdownMenuItem>
                        <DropdownMenuItem
                          @click="() => processNow(pipeline)"
                          class="flex items-center"
                          :disabled="pipeline.status.name !== 'ACTIVE'"
                        >
                          <Activity class="w-4 h-4 mr-2" />
                          Process Now
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                          @click="() => deletePipeline(pipeline)"
                          class="flex items-center text-destructive focus:text-destructive"
                        >
                          <Trash2 class="w-4 h-4 mr-2" />
                          Delete Pipeline
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
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
              <span class="font-semibold text-foreground">{{ ((paginator.currentPage - 1) * paginator.perPage) + 1 }}</span>
              to
              <span class="font-semibold text-foreground">{{ Math.min(paginator.currentPage * paginator.perPage, paginator.total) }}</span>
              of
              <span class="font-semibold text-foreground">{{ paginator.total }}</span>
              {{ paginator.total === 1 ? 'result' : 'results' }}
            </div>
            <Pagination :paginator="paginator" />
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Delete Confirmation Dialog -->
    <AlertDialog v-model:open="deleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete Pipeline</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to delete the pipeline
            <span class="font-semibold text-foreground">"{{ pipelineToDelete?.name }}"</span>?
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
