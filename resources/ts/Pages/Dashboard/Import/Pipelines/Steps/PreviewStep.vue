<script setup lang="ts">
import { computed, ref } from 'vue'
import PipelineStepLayout from '../Partials/PipelineStepLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table'
import {
  CheckCircle2,
  XCircle,
  AlertCircle,
  Database,
  Clock,
  FileText,
  Filter as FilterIcon
} from 'lucide-vue-next'
import type { PreviewStepViewModel } from '@/types/generated'

const props = defineProps<PreviewStepViewModel>()

const emit = defineEmits<{
  save: []
  saveAndNext: []
}>()

// Handle save (no-op for preview step)
const handleSave = () => {
  emit('save')
}

// Handle save and next (no-op for preview step)
const handleSaveAndNext = () => {
  emit('saveAndNext')
}

// Computed properties
const previewData = computed(() => props.previewData || [])
const columns = computed(() => props.columns || [])
const stats = computed(() => props.stats || {})
const errors = computed(() => props.errors || [])
const hasError = computed(() => props.hasError || false)
const hasResult = computed(() => props.hasResult || false)

// Format cell value for display
const formatCellValue = (value: any): string => {
  if (value === null || value === undefined) {
    return '—'
  }
  if (typeof value === 'object') {
    return JSON.stringify(value)
  }
  return String(value)
}

// Get success rate
const successRate = computed(() => {
  if (!stats.value.total_rows || stats.value.total_rows === 0) {
    return 0
  }
  const processed = stats.value.filtered_rows || stats.value.mapped_rows || 0
  return Math.round((processed / stats.value.total_rows) * 100)
})

// Dialog state for showing full cell content
const isDialogOpen = ref(false)
const dialogContent = ref<string>('')
const dialogTitle = ref<string>('')

// Check if text is truncated (rough estimate based on length)
const isTextTruncated = (value: string): boolean => {
  // If text is longer than ~30 characters, it's likely truncated in a 200px cell
  return value.length > 30
}

// Handle cell click
const handleCellClick = (value: any, column: string) => {
  const formattedValue = formatCellValue(value)
  if (isTextTruncated(formattedValue)) {
    dialogContent.value = formattedValue
    dialogTitle.value = column
    isDialogOpen.value = true
  }
}
</script>

<template>
  <PipelineStepLayout
    :stepper="props.stepper"
    @save="handleSave"
    @save-and-next="handleSaveAndNext"
  >
    <div class="space-y-6 max-w-7xl mx-auto">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold">Pipeline Preview</h2>
          <p class="text-muted-foreground">Review the processed data from your pipeline configuration</p>
        </div>
      </div>

      <!-- Error Alert -->
      <Alert v-if="hasError" variant="destructive">
        <AlertCircle class="h-4 w-4" />
        <AlertTitle>Pipeline Execution Failed</AlertTitle>
        <AlertDescription>
          {{ props.error }}
        </AlertDescription>
      </Alert>

      <!-- Statistics Cards -->
      <div v-if="hasResult" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Rows -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Rows</CardTitle>
            <Database class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total_rows || 0 }}</div>
            <p class="text-xs text-muted-foreground">Rows processed</p>
          </CardContent>
        </Card>

        <!-- Mapped Rows -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Mapped Rows</CardTitle>
            <FileText class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.mapped_rows || 0 }}</div>
            <p class="text-xs text-muted-foreground">Successfully mapped</p>
          </CardContent>
        </Card>

        <!-- Filtered Rows -->
        <Card v-if="stats.filtered_rows !== undefined">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Filtered Rows</CardTitle>
            <FilterIcon class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.filtered_rows || 0 }}</div>
            <p class="text-xs text-muted-foreground">After filtering</p>
          </CardContent>
        </Card>

        <!-- Processing Time -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Processing Time</CardTitle>
            <Clock class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.processing_time || 0 }}s</div>
            <p class="text-xs text-muted-foreground">Execution time</p>
          </CardContent>
        </Card>
      </div>

      <!-- Success Rate & Errors -->
      <div v-if="hasResult" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Success Rate -->
        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Success Rate</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex items-center gap-4">
              <div class="flex-1">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm text-muted-foreground">Completion</span>
                  <span class="text-sm font-medium">{{ successRate }}%</span>
                </div>
                <div class="w-full bg-secondary rounded-full h-2">
                  <div
                    class="bg-primary h-2 rounded-full transition-all"
                    :style="{ width: `${successRate}%` }"
                  ></div>
                </div>
              </div>
              <Badge :variant="successRate >= 90 ? 'default' : successRate >= 50 ? 'secondary' : 'destructive'">
                <CheckCircle2 v-if="successRate >= 90" class="h-3 w-3 mr-1" />
                <AlertCircle v-else-if="successRate >= 50" class="h-3 w-3 mr-1" />
                <XCircle v-else class="h-3 w-3 mr-1" />
                {{ successRate >= 90 ? 'Excellent' : successRate >= 50 ? 'Good' : 'Needs Attention' }}
              </Badge>
            </div>
          </CardContent>
        </Card>

        <!-- Error Count -->
        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Errors</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex items-center gap-4">
              <div class="text-2xl font-bold" :class="stats.error_count > 0 ? 'text-destructive' : 'text-green-600'">
                {{ stats.error_count || 0 }}
              </div>
              <div class="flex-1">
                <p class="text-sm text-muted-foreground">
                  {{ stats.error_count === 0 ? 'No errors detected' : 'Errors found during processing' }}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Errors List -->
      <Card v-if="hasResult && errors.length > 0">
        <CardHeader>
          <CardTitle class="text-lg flex items-center gap-2">
            <AlertCircle class="h-5 w-5 text-destructive" />
            Processing Errors
          </CardTitle>
          <CardDescription>
            {{ errors.length }} error(s) occurred during pipeline execution
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-2 max-h-60 overflow-y-auto">
            <Alert
              v-for="(error, index) in errors.slice(0, 10)"
              :key="index"
              variant="destructive"
              class="text-sm"
            >
              <AlertDescription>
                {{ typeof error === 'string' ? error : JSON.stringify(error) }}
              </AlertDescription>
            </Alert>
            <p v-if="errors.length > 10" class="text-sm text-muted-foreground text-center pt-2">
              ... and {{ errors.length - 10 }} more error(s)
            </p>
          </div>
        </CardContent>
      </Card>

      <!-- Preview Data Table -->
      <Card v-if="hasResult">
        <CardHeader>
          <CardTitle class="text-lg">Preview Data</CardTitle>
          <CardDescription>
            Showing first {{ previewData.length }} of {{ stats.total_rows || 0 }} rows
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="previewData.length === 0" class="text-center py-12 text-muted-foreground">
            <Database class="h-12 w-12 mx-auto mb-4 opacity-50" />
            <p class="text-lg font-medium">No data to display</p>
            <p class="text-sm">The pipeline processed successfully but no data was returned</p>
          </div>

          <div v-else class="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead class="w-12">#</TableHead>
                  <TableHead
                    v-for="column in columns"
                    :key="column"
                    class="min-w-[150px]"
                  >
                    {{ column }}
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow
                  v-for="(row, rowIndex) in previewData"
                  :key="rowIndex"
                >
                  <TableCell class="font-medium text-muted-foreground">
                    {{ rowIndex + 1 }}
                  </TableCell>
                  <TableCell
                    v-for="column in columns"
                    :key="column"
                    class="max-w-[200px] truncate"
                    :class="{
                      'cursor-pointer hover:bg-muted/50 transition-colors': isTextTruncated(formatCellValue(row[column]))
                    }"
                    :title="formatCellValue(row[column])"
                    @click="handleCellClick(row[column], column)"
                  >
                    {{ formatCellValue(row[column]) }}
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <div v-if="previewData.length < (stats.total_rows || 0)" class="mt-4 text-center">
            <p class="text-sm text-muted-foreground">
              Showing preview of {{ previewData.length }} rows. Total rows: {{ stats.total_rows || 0 }}
            </p>
          </div>
        </CardContent>
      </Card>

      <!-- No Result State -->
      <Card v-if="!hasResult && !hasError">
        <CardContent class="text-center py-12">
          <Database class="h-12 w-12 mx-auto mb-4 opacity-50 text-muted-foreground" />
          <p class="text-lg font-medium text-muted-foreground">No preview data available</p>
          <p class="text-sm text-muted-foreground mt-2">
            Configure and save your pipeline steps to see a preview
          </p>
        </CardContent>
      </Card>
    </div>

    <!-- Dialog for showing full cell content -->
    <Dialog v-model:open="isDialogOpen">
      <DialogContent class="max-w-2xl max-h-[80vh] w-[calc(100vw-2rem)] sm:w-full overflow-hidden">
        <DialogHeader class="min-w-0">
          <DialogTitle class="break-words pr-8">{{ dialogTitle }}</DialogTitle>
          <DialogDescription>
            Full content of the selected cell
          </DialogDescription>
        </DialogHeader>
        <div class="mt-4 min-w-0 overflow-hidden">
          <div class="rounded-md border bg-muted/50 p-4 max-h-[60vh] overflow-y-auto overflow-x-hidden">
            <pre class="whitespace-pre-wrap break-words break-all text-sm font-mono overflow-wrap-anywhere word-break-break-all min-w-0 max-w-full">{{ dialogContent }}</pre>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </PipelineStepLayout>
</template>

