<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import PipelineStepLayout from '../Partials/PipelineStepLayout.vue'
import ReaderTypeSelector from './Partials/reader/ReaderTypeSelector.vue'
import CsvConfig from './Partials/reader/CsvConfig.vue'
import JsonConfig from './Partials/reader/JsonConfig.vue'
import XmlConfig from './Partials/reader/XmlConfig.vue'
import { Form } from '@/components/ui/form'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { Loader2, CheckCircle2, XCircle, ChevronDown } from 'lucide-vue-next'
import { useTestReader } from '@/composables/useTestReader'
import { READER_TYPES } from '@/constants/reader-types'
import { ReaderConfigStepViewModel } from '@/types/generated'

const props = defineProps<ReaderConfigStepViewModel>()

const emit = defineEmits<{
  save: []
  saveAndNext: []
}>()

// Form state
const form = useForm({
  reader_type: props.readerType || 'csv',
  options: {
    // CSV options
    delimiter: props.delimiter || ',',
    enclosure: props.enclosure || '"',
    escape: props.escape || '\\',
    has_header: props.hasHeader ?? true,
    trim: props.trim ?? true,
    // JSON/XML options
    entry_point: props.entryPoint || '',
    // XML options
    keep_root: props.keepRoot ?? false,
  }
})

// Track test result collapsible state
const isTestResultOpen = ref(true)

// Test reader functionality
const { isTesting, testReader: testReaderFn } = useTestReader()

// Get test result from props
const testResult = computed(() => {
  const result = props.testResult as any
  if (!result) {
    return null
  }
  return {
    success: result.success,
    message: result.message,
    details: result.details
  }
})

// Computed properties
const isCsvType = computed(() => form.reader_type === 'csv')
const isJsonType = computed(() => form.reader_type === 'json')
const isXmlType = computed(() => form.reader_type === 'xml')

// Methods
const updateOptionsForReaderType = (readerType: string) => {
  const readerConfig = READER_TYPES[readerType as keyof typeof READER_TYPES]
  if (readerConfig?.defaultOptions) {
    // Reset options to defaults for the selected reader type
    Object.assign(form.options, readerConfig.defaultOptions)
  }
}

// Watch for reader type changes and update options accordingly
watch(() => form.reader_type, (newType) => {
  updateOptionsForReaderType(newType)
})

const testReader = async () => {
  if (isTesting.value) {
    return
  }

  const data = form.data()
  testReaderFn(props.pipeline.id, data)
}

const handleSave = () => {
  if (form.processing) {
    return
  }

  form.post(route('dashboard.import.pipelines.step.store', {
    pipeline: props.pipeline.id,
    step: 'reader-config'
  }), {
    onSuccess: () => {
      emit('save')
    },
    preserveState: true,
    preserveScroll: true,
  })
}

const handleSaveAndNext = () => {
  form.post(route('dashboard.import.pipelines.step.store', {
    pipeline: props.pipeline.id,
    step: 'reader-config'
  }), {
    onSuccess: () => {
      emit('saveAndNext')
    }
  })
}
</script>

<template>
  <PipelineStepLayout
      :stepper="props.stepper"
      @save="handleSave"
      @save-and-next="handleSaveAndNext"
  >
    <div class="space-y-8 max-w-6xl mx-auto">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b">
        <div class="space-y-1.5">
          <h2 class="text-2xl font-bold tracking-tight">Data Reader</h2>
          <p class="text-sm text-muted-foreground leading-relaxed">
            Configure how to parse and read your data
          </p>
        </div>
      </div>

      <Form :form="form" @submit="handleSaveAndNext">
        <!-- Reader Type Selection -->
        <ReaderTypeSelector
            :selected-type="form.reader_type"
            @update:selected-type="form.reader_type = $event"
        />

        <!-- Configuration based on reader type -->
        <div class="mt-6">
          <!-- CSV Configuration -->
          <div v-if="isCsvType">
            <CsvConfig :form="form"/>
          </div>

          <!-- JSON Configuration -->
          <div v-else-if="isJsonType">
            <JsonConfig :form="form"/>
          </div>

          <!-- XML Configuration -->
          <div v-else-if="isXmlType">
            <XmlConfig :form="form"/>
          </div>

        </div>

        <!-- Test Reader Section -->
        <Card class="mt-8 border-2 shadow-sm">
          <CardHeader class="pb-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div class="space-y-1">
                <CardTitle class="text-lg font-semibold">Test Reader</CardTitle>
                <CardDescription class="text-sm">
                  Save configuration and test the reader parsing
                </CardDescription>
              </div>
              <Button
                  type="button"
                  variant="outline"
                  size="lg"
                  :disabled="isTesting || form.processing"
                  @click="testReader"
                  class="w-full sm:w-auto"
              >
                <Loader2 v-if="isTesting" class="w-4 h-4 mr-2 animate-spin" />
                <span v-if="isTesting">Testing...</span>
                <span v-else>Save & Test</span>
              </Button>
            </div>
          </CardHeader>
          <CardContent class="pt-0">
            <!-- Test Result -->
            <Collapsible v-if="testResult" v-model:open="isTestResultOpen" class="mt-4">
              <div
                  class="p-4 rounded-lg border-2 transition-all duration-200"
                  :class="{
                  'bg-green-50/50 dark:bg-green-950/20 border-green-500/50': testResult.success,
                  'bg-red-50/50 dark:bg-red-950/20 border-red-500/50': !testResult.success
                }"
              >
                <CollapsibleTrigger as-child>
                  <button class="flex items-start gap-3 w-full text-left">
                    <div class="flex-shrink-0 mt-0.5">
                      <CheckCircle2
                          v-if="testResult.success"
                          class="h-5 w-5 text-green-600 dark:text-green-400"
                      />
                      <XCircle
                          v-else
                          class="h-5 w-5 text-red-600 dark:text-red-400"
                      />
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-semibold mb-1" :class="{
                        'text-green-900 dark:text-green-100': testResult.success,
                        'text-red-900 dark:text-red-100': !testResult.success
                      }">
                        {{ testResult.message }}
                      </p>
                    </div>
                    <ChevronDown 
                      class="h-4 w-4 transition-transform duration-200 mt-0.5 shrink-0"
                      :class="{ 'rotate-180': isTestResultOpen }"
                    />
                  </button>
                </CollapsibleTrigger>
                <CollapsibleContent>
                  <div v-if="testResult.details" class="mt-3">
                    <div class="p-3 bg-background/50 rounded border text-xs font-mono overflow-x-auto">
                      <pre class="whitespace-pre-wrap break-words">{{ JSON.stringify(testResult.details, null, 2) }}</pre>
                    </div>
                  </div>
                </CollapsibleContent>
              </div>
            </Collapsible>
            <div v-else-if="!isTesting" class="text-center py-8 text-sm text-muted-foreground">
              Click "Save & Test" to verify your reader configuration
            </div>
          </CardContent>
        </Card>
      </Form>
    </div>
  </PipelineStepLayout>
</template>
